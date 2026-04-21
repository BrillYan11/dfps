<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM800C SMS Sender</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f3f3;
            padding: 40px;
        }
        .box {
            max-width: 500px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.12);
        }
        input, textarea, button {
            width: 100%;
            padding: 12px;
            margin-top: 12px;
            box-sizing: border-box;
            font-size: 15px;
        }
        textarea {
            height: 120px;
            resize: vertical;
        }
        button {
            background: #1677ff;
            color: white;
            border: 0;
            cursor: pointer;
        }
        button:hover {
            background: #0f5fd1;
        }
        .ok {
            color: green;
            margin-top: 15px;
        }
        .bad {
            color: red;
            margin-top: 15px;
        }
        pre {
            background: #111;
            color: #0f0;
            padding: 10px;
            border-radius: 8px;
            overflow: auto;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>SIM800C SMS Sender</h2>

        <form id="smsForm">
            <input type="text" name="phone" placeholder="+639506225047" required>
            <textarea name="message" placeholder="Type your message here..." required></textarea>
            <button type="submit">Send SMS</button>
        </form>

        <div id="result"></div>
    </div>

    <script>
document.getElementById("smsForm").addEventListener("submit", async function(e) {
    e.preventDefault();

    const result = document.getElementById("result");
    const formData = new FormData(this);
    const button = this.querySelector("button");

    button.disabled = true;
    button.textContent = "Sending...";
    result.innerHTML = "<p>Please wait...</p>";

    try {
        const res = await fetch("send_sms.php", {
            method: "POST",
            body: formData
        });

        const text = await res.text();

        let data;
        try {
            data = JSON.parse(text);
        } catch {
            result.innerHTML = `
                <div class="bad"><strong>Invalid server response</strong></div>
                <pre>${text.replace(/</g, "&lt;")}</pre>
            `;
            return;
        }

        result.innerHTML = `
            <div class="${data.success ? 'ok' : 'bad'}"><strong>${data.message}</strong></div>
            <pre>${JSON.stringify(data, null, 2)}</pre>
        `;
    } catch (err) {
        result.innerHTML = `
            <div class="bad"><strong>Request failed</strong></div>
            <pre>${err.message}</pre>
        `;
    } finally {
        button.disabled = false;
        button.textContent = "Send SMS";
    }
});
</script>
</body>
</html>