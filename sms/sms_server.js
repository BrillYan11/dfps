const http = require("http");
const { SerialPort } = require("serialport");

const MODEM_PORT = process.env.MODEM_PORT || "COM5";
const BAUD_RATE = parseInt(process.env.BAUD_RATE) || 9600;
const HTTP_PORT = parseInt(process.env.HTTP_PORT) || 3001;

const port = new SerialPort({
  path: MODEM_PORT,
  baudRate: BAUD_RATE,
  autoOpen: false
});

let modemBuffer = "";
let modemReady = false;
let busy = false;

function wait(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

function openPort() {
  return new Promise((resolve, reject) => {
    if (port.isOpen) return resolve();
    port.open(err => err ? reject(err) : resolve());
  });
}

function writeRaw(data) {
  return new Promise((resolve, reject) => {
    port.write(data, err => {
      if (err) return reject(err);
      port.drain(err2 => err2 ? reject(err2) : resolve());
    });
  });
}

async function sendCommand(cmd, delay = 700) {
  modemBuffer = "";
  await writeRaw(cmd + "\r");
  await wait(delay);
  return modemBuffer;
}

async function initModem() {
  await openPort();
  await wait(1500);

  await sendCommand("ATE0", 400);
  const r = await sendCommand("AT+CMGF=1", 700);

  if (!r.includes("OK")) {
    throw new Error("Failed to initialize modem text mode: " + r);
  }

  modemReady = true;
  console.log("Modem ready on", MODEM_PORT);
}

async function sendSMS(phone, message) {
  if (busy) {
    throw new Error("Modem is busy. Try again in a moment.");
  }

  busy = true;

  try {
    if (!port.isOpen) {
      await initModem();
    }

    const cmgf = await sendCommand("AT+CMGF=1", 500);
    if (!cmgf.includes("OK")) {
      throw new Error("CMGF failed: " + cmgf);
    }

    const cmgs = await sendCommand(`AT+CMGS="${phone}"`, 1000);
    if (!cmgs.includes(">")) {
      throw new Error("No prompt from modem: " + cmgs);
    }

    modemBuffer = "";
    await writeRaw(message + String.fromCharCode(26));
    await wait(4500);

    const finalResponse = modemBuffer;

    if (finalResponse.includes("OK") || finalResponse.includes("+CMGS:")) {
      return {
        success: true,
        message: "SMS sent successfully",
        log: finalResponse
      };
    }

    return {
      success: false,
      message: "SMS failed",
      log: finalResponse
    };
  } finally {
    busy = false;
  }
}

port.on("data", data => {
  modemBuffer += data.toString();
});

port.on("error", err => {
  modemReady = false;
  console.error("Serial error:", err.message);
});

const server = http.createServer(async (req, res) => {
  res.setHeader("Access-Control-Allow-Origin", "*");
  res.setHeader("Access-Control-Allow-Methods", "POST, OPTIONS");
  res.setHeader("Access-Control-Allow-Headers", "Content-Type");

  if (req.method === "OPTIONS") {
    res.writeHead(204);
    res.end();
    return;
  }

  if (req.method === "GET" && req.url === "/status") {
    res.writeHead(200, { "Content-Type": "application/json" });
    res.end(JSON.stringify({
      success: true,
      portOpen: port.isOpen,
      modemReady,
      busy
    }));
    return;
  }

  if (req.method === "POST" && req.url === "/send-sms") {
    let body = "";

    req.on("data", chunk => {
      body += chunk.toString();
    });

    req.on("end", async () => {
      try {
        const data = JSON.parse(body || "{}");
        const phone = (data.phone || "").trim();
        const message = (data.message || "").trim();

        if (!phone || !message) {
          res.writeHead(400, { "Content-Type": "application/json" });
          res.end(JSON.stringify({
            success: false,
            message: "Phone and message are required"
          }));
          return;
        }

        const result = await sendSMS(phone, message);

        res.writeHead(result.success ? 200 : 500, {
          "Content-Type": "application/json"
        });
        res.end(JSON.stringify(result));
      } catch (err) {
        res.writeHead(500, { "Content-Type": "application/json" });
        res.end(JSON.stringify({
          success: false,
          message: err.message
        }));
      }
    });

    return;
  }

  res.writeHead(404, { "Content-Type": "application/json" });
  res.end(JSON.stringify({
    success: false,
    message: "Not found"
  }));
});

server.listen(HTTP_PORT, async () => {
  console.log(`SMS server running at http://127.0.0.1:${HTTP_PORT}`);
  try {
    await initModem();
  } catch (err) {
    console.error("Initial modem setup failed:", err.message);
  }
});