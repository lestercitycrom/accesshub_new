<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>{{ $title ?? 'Game Delivery' }}</title>
	<style>
		:root {
			color-scheme: dark;
			--bg: #08111f;
			--panel: #101b2d;
			--line: rgba(255,255,255,.12);
			--text: #eef4ff;
			--muted: #9fb0c8;
			--accent: #6d5dfc;
			--danger: #ef5565;
			--ok: #32d583;
		}
		* { box-sizing: border-box; }
		body {
			margin: 0;
			font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
			background: var(--bg);
			color: var(--text);
		}
		main { width: min(960px, 100%); margin: 0 auto; padding: 32px 16px; }
		.shell { border: 1px solid var(--line); background: var(--panel); border-radius: 8px; padding: 24px; }
		.header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; }
		.brand { font-weight: 700; letter-spacing: 0; }
		.muted { color: var(--muted); }
		.grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
		.card { border: 1px solid var(--line); border-radius: 8px; padding: 16px; background: rgba(255,255,255,.03); }
		label { display: block; font-size: 14px; color: var(--muted); margin-bottom: 6px; }
		input, select, button {
			width: 100%;
			border: 1px solid var(--line);
			border-radius: 6px;
			padding: 12px;
			background: rgba(0,0,0,.24);
			color: var(--text);
			font: inherit;
		}
		button { background: var(--accent); border-color: transparent; cursor: pointer; font-weight: 700; }
		button.secondary { background: transparent; border-color: var(--line); }
		.form-row { margin-bottom: 14px; }
		.status { display: inline-flex; align-items: center; border: 1px solid var(--line); border-radius: 999px; padding: 6px 10px; font-size: 13px; }
		.error { color: var(--danger); font-size: 14px; margin-top: 6px; }
		.code { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; word-break: break-all; }
		.hidden { display: none; }
		@media (max-width: 720px) {
			main { padding: 18px 12px; }
			.shell { padding: 18px; }
			.grid { grid-template-columns: 1fr; }
			.header { align-items: flex-start; flex-direction: column; }
		}
	</style>
</head>
<body>
	<main>
		<div class="header">
			<div class="brand">Game Delivery</div>
			<div class="muted">Secure order access</div>
		</div>
		{{ $slot }}
	</main>
</body>
</html>

