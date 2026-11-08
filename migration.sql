ALTER TABLE users
  ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN otp_hash VARCHAR(255) NULL,
  ADD COLUMN otp_expires_at DATETIME NULL,
  ADD COLUMN otp_attempts TINYINT NOT NULL DEFAULT 0,
  ADD COLUMN otp_sent_at DATETIME NULL;

-- para hindi ma-lock out yung existing users
UPDATE users SET email_verified = 1;