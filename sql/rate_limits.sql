-- Rate limiting table for login, forgot password, etc.
CREATE TABLE IF NOT EXISTS rate_limits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  action_type VARCHAR(50) NOT NULL,
  window_start INT NOT NULL,
  hits INT NOT NULL DEFAULT 1,
  KEY idx_ip_action (ip_address, action_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
