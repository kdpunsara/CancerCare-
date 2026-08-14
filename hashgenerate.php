<?php
// Generate a fresh password hash
$password = "doctor"; // The password you want to use
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: " . $password . "\n";
echo "Hash: " . $hash . "\n";
echo "\nUse this SQL command:\n";
echo "UPDATE User SET password_hash = '" . $hash . "' WHERE username = 'dr_noel';\n";
?>