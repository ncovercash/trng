<?php

if (!isset($_GET["min"]) || !isset($_GET["max"]) || !isset($_GET["num"]) || (isset($_GET["unique"]) && $_GET["unique"] !== "on")) {
	die("Invalid Request.");
}

$min = (int)$_GET["min"];
$max = (int)$_GET["max"];
$num = (int)$_GET["num"];

if ($min != (int)$_GET["min"] || $max != (int)$_GET["max"] || $num != (int)$_GET["num"]) {
	die("Invalid Request.");
}

$unique = isset($_GET["unique"]) && ($_GET["unique"] === "on");

if ($unique && $num > ($max - $min + 1)) { // +1 as it is inclusive
	die("You requested unique values, however, the domain is too small.");
}

$result = file_get_contents(
	"https://api.random.org/json-rpc/1/invoke",
	false,
	stream_context_create([
		"http" => [
			"ignore_errors" => true,
			"headers" => implode("\r\n",[
				"Content-Type: application/json-rpc",
			]),
			"content" => json_encode([
				"jsonrpc" => "2.0",
				"method" => "generateIntegers",
				"params" => [
					"apiKey" => getenv("TRNG_RANDOM_TOKEN"),
					"n" => $num,
					"min" => $min,
					"max" => $max,
					"replacement" => !$unique,
				],
				"id" => 0,
			]),
		]
	])
);

$result = json_decode($result);

if (isset($result->error)) {
	header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error", true, 500);
	echo "An error occurred.  This error has been reported to the webmaster, please try again later.\n";
	mail("www-data@ncovercash.dev","TRNG error report", $result->error->message);
	die();
}
$out = implode(", ",$result->result->random->data);
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
		<title>
			TRNG Results | Noah Overcash
		</title>

		<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js" defer></script>
		
		<link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet"/>
		<style type="text/css">
			.flow-text {
				font-weight: 300;
			}
		</style>
	</head>
	<body>
		<main>
			<div class="container">
				<div class="section">
					<p class="flow-text">Minimum: <strong><?= $min ?></strong></p>
					<p class="flow-text">Maximum: <strong><?= $max ?></strong></p>
					<p class="flow-text">Quantity: <strong><?= $num ?></strong></p>
					<p class="flow-text">Unique: <strong><?= $unique ? "yes" : "no" ?></strong></p>
					<p class="flow-text">Result: <strong><?= $out ?></strong></p>
				</div>
			</div>
		</main>
	</body>
</html>
