<?php
// safe local cookie refresher

function csrf($cookie) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://auth.roblox.com/v2/login");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("{}")));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Cookie: .ROBLOSECURITY=$cookie"
    ));
    $output = curl_exec($ch);
    curl_close($ch);

    preg_match('/X-CSRF-TOKEN:\s*(\S+)/i', $output, $matches);
    return $matches[1] ?? null;
}

function refresh($cookie) {
    $csrf = csrf($cookie);
    if (!$csrf) return "invalid or rate-limited";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://auth.roblox.com/v1/authentication-ticket");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("{}")));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "origin: https://www.roblox.com",
        "Referer: https://www.roblox.com/games/920587237/Adopt-Me",
        "x-csrf-token: $csrf",
        "Cookie: .ROBLOSECURITY=$cookie"
    ));
    $output = curl_exec($ch);
    curl_close($ch);

    preg_match('/rbx-authentication-ticket:\s*([^\s]+)/i', $output, $matches);
    $ticket = $matches[1] ?? null;
    if (!$ticket) return "failed to get ticket";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://auth.roblox.com/v1/authentication-ticket/redeem");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("authenticationTicket" => $ticket)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "origin: https://www.roblox.com",
        "Referer: https://www.roblox.com/games/920587237/Adopt-Me",
        "x-csrf-token: $csrf",
        "RBXAuthenticationNegotiation: 1"
    ));
    $output = curl_exec($ch);
    curl_close($ch);

    if (strpos($output, ".ROBLOSECURITY=") === false) return "failed to refresh";

    preg_match('/\.ROBLOSECURITY=([^;]+)/', $output, $matches);
    return $matches[1] ?? "failed to parse refreshed cookie";
}

// handle form submission
$refreshedCookie = "";
if (isset($_POST['cookie'])) {
    $refreshedCookie = refresh($_POST['cookie']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Roblox Cookie Refresher</title>
</head>
<body>
<h2>Safe Local Roblox Cookie Refresher</h2>
<form method="post">
    <input type="text" name="cookie" placeholder="Enter your .ROBLOSECURITY" style="width:300px;">
    <button type="submit">Refresh</button>
</form>

<?php if ($refreshedCookie): ?>
    <p><strong>Refreshed Cookie:</strong> <?php echo htmlspecialchars($refreshedCookie); ?></p>
<?php endif; ?>
</body>
</html>
