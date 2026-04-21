const { SerialPort } = require("serialport");

const phone = process.argv[2];
const message = process.argv[3];

if (!phone || !message) {
  console.log(JSON.stringify({
    success: false,
    message: "Missing phone or message"
  }));
  process.exit(1);
}

const port = new SerialPort({
  path: "COM5",
  baudRate: 9600,
  autoOpen: false
});

let buffer = "";

function wait(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

function openPort() {
  return new Promise((resolve, reject) => {
    port.open(err => err ? reject(err) : resolve());
  });
}

function closePortSafe() {
  return new Promise(resolve => {
    if (!port.isOpen) return resolve();
    port.close(() => resolve());
  });
}

async function send(cmd, delay = 800) {
  buffer = "";
  port.write(cmd + "\r");
  await wait(delay);
  return buffer;
}

async function run() {
  try {
    port.on("data", data => {
      buffer += data.toString();
    });

    await openPort();
    await wait(1000);

    await send("ATE0", 500);

    const cmgf = await send("AT+CMGF=1", 800);
    if (!cmgf.includes("OK")) {
      await closePortSafe();
      console.log(JSON.stringify({
        success: false,
        message: "Failed to set text mode",
        log: { CMGF: cmgf }
      }));
      return;
    }

    const cmgs = await send(`AT+CMGS="${phone}"`, 1200);
    if (!cmgs.includes(">")) {
      await closePortSafe();
      console.log(JSON.stringify({
        success: false,
        message: "No > prompt from modem",
        log: { CMGS: cmgs }
      }));
      return;
    }

    buffer = "";
    port.write(message + String.fromCharCode(26));
    await wait(4000);

    const finalResponse = buffer;

    await closePortSafe();

    console.log(JSON.stringify({
      success: finalResponse.includes("OK") || finalResponse.includes("+CMGS:"),
      message: finalResponse.includes("OK") || finalResponse.includes("+CMGS:")
        ? "SMS sent successfully"
        : "SMS failed",
      log: { FINAL: finalResponse }
    }));
  } catch (err) {
    await closePortSafe();
    console.log(JSON.stringify({
      success: false,
      message: err.message
    }));
  }
}

run();