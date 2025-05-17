const express = require("express");
const bodyParser = require("body-parser");
const nodemailer = require("nodemailer");
const cors = require("cors");
const mysql = require("mysql2");

const app = express();
app.use(cors());
app.use(bodyParser.json());

// ✅ MySQL connection
const db = mysql.createConnection({
    host: "localhost",
    user: "root",           // your MySQL username
    password: "",           // your MySQL password
    database: "auth_db"     // your MySQL database
});

// ✅ Connect to DB
db.connect((err) => {
    if (err) {
        console.error("❌ Error connecting to MySQL:", err);
        return;
    }
    console.log("✅ Connected to MySQL database");
});

// ✅ Nodemailer transporter
const transporter = nodemailer.createTransport({
    service: "gmail",
    auth: {
        user: "panganibanmarkjohn9@gmail.com",
        pass: "gkpr lqyf tbip mjlz"
    }
});

// ✅ Send OTP and store in DB
function sendOTP(email, res, isResend = false) {
    const otp = Math.floor(100000 + Math.random() * 900000).toString();

    const mailOptions = {
        from: "panganibanmarkjohn9@gmail.com",
        to: email,
        subject: isResend ? "Your Resent OTP Code" : "Your OTP Code",
        text: `Your OTP is: ${otp}`
    };

    transporter.sendMail(mailOptions, (error, info) => {
        if (error) {
            console.error("❌ Error sending email:", error);
            return res.status(500).send("Failed to send OTP.");
        }

        // ✅ Save or update OTP in database
        const query = `
            INSERT INTO otps (email, otp)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE
            otp = VALUES(otp), created_at = CURRENT_TIMESTAMP;
        `;
        db.query(query, [email, otp], (err) => {
            if (err) {
                console.error("❌ DB error saving OTP:", err);
                return res.status(500).send("Failed to store OTP.");
            }

            console.log(`${isResend ? "🔁 Resent" : "📤 Sent"} OTP ${otp} to ${email}`);
            res.send(`${isResend ? "Resent" : "Sent"} OTP successfully`);
        });
    });
}

// ✅ Send OTP route
app.post("/send-otp", (req, res) => {
    const { email } = req.body;
    if (!email) return res.status(400).send("Email is required.");
    sendOTP(email, res);
});

// ✅ Resend OTP route
app.post("/resend-otp", (req, res) => {
    const { email } = req.body;
    if (!email) return res.status(400).send("Email is required.");

    db.query("SELECT * FROM otps WHERE email = ?", [email], (err, results) => {
        if (err) return res.status(500).send("DB error.");
        if (results.length === 0) {
            return res.status(400).send("No OTP found. Use /send-otp first.");
        }

        sendOTP(email, res, true);
    });
});

// ✅ Verify OTP route
app.post("/verify-otp", (req, res) => {
    const { email, otp } = req.body;
    if (!email || !otp) {
        return res.status(400).json({ message: "Email and OTP are required." });
    }

    const query = "SELECT * FROM otps WHERE email = ? AND otp = ?";
    db.query(query, [email, otp], (err, results) => {
        if (err) return res.status(500).send("DB error.");

        if (results.length > 0) {
            // ✅ Success — delete the OTP
            db.query("DELETE FROM otps WHERE email = ?", [email]);
            console.log(`✅ OTP verified for ${email}`);
            res.json({ message: "OTP verified successfully" });
        } else {
            console.log(`❌ Invalid OTP for ${email}`);
            res.json({ message: "Invalid OTP!" });
        }
    });
});

// ✅ Start server
app.listen(3000, () => {
    console.log("🚀 Server running at http://localhost:3000");
});
