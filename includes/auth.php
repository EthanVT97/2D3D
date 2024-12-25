<?php
session_start();
require_once __DIR__ . '/../config/database.php';

class Auth
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function register($username, $email, $phone, $password)
    {
        try {
            // Check if user already exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? OR phone = ?");
            $stmt->execute([$username, $email, $phone]);

            if ($stmt->rowCount() > 0) {
                return [
                    'success' => false,
                    'message' => 'အသုံးပြုသူအမည်၊ အီးမေးလ် သို့မဟုတ် ဖုန်းနံပါတ် ရှိပြီးသားဖြစ်ပါသည်။'
                ];
            }

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $this->conn->prepare(
                "INSERT INTO users (username, email, phone, password) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$username, $email, $phone, $hashedPassword]);

            return [
                'success' => true,
                'message' => 'အကောင့်ဖွင့်ခြင်း အောင်မြင်ပါသည်။'
            ];
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'စနစ်တွင် အမှားတစ်ခု ဖြစ်ပေါ်နေပါသည်။ နောက်မှ ထပ်မံကြိုးစားကြည့်ပါ။'
            ];
        }
    }

    public function login($username, $password)
    {
        try {
            $stmt = $this->conn->prepare(
                "SELECT id, username, password, role, status FROM users WHERE username = ?"
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'အသုံးပြုသူအမည် သို့မဟုတ် စကားဝှက် မှားယွင်းနေပါသည်။'
                ];
            }

            if ($user['status'] !== 'active') {
                return [
                    'success' => false,
                    'message' => 'သင့်အကောင့် ပိတ်ထားပါသည်။ အက်ဒမင်ကို ဆက်သွယ်ပါ။'
                ];
            }

            // Update last login
            $stmt = $this->conn->prepare(
                "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?"
            );
            $stmt->execute([$user['id']]);

            // Set session
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ];

            return [
                'success' => true,
                'message' => 'အကောင့်ဝင်ရောက်ခြင်း အောင်မြင်ပါသည်။'
            ];
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'စနစ်တွင် အမှားတစ်ခု ဖြစ်ပေါ်နေပါသည်။ နောက်မှ ထပ်မံကြိုးစားကြည့်ပါ။'
            ];
        }
    }
}
