<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>{{ $title ?? 'Game Delivery' }}</title>
	<style>
		:root {
			--bg-from: #f5f7fa;
			--bg-to: #c3cfe2;
			--grad-from: #667eea;
			--grad-to: #764ba2;
			--panel: #ffffff;
			--panel-soft: #f8f9fb;
			--line: #e6e9f0;
			--text: #1f2430;
			--muted: #6b7280;
			--accent: #667eea;
			--danger: #dc3545;
			--danger-soft: #fdecee;
			--ok: #198754;
			--ok-soft: #e8f5ee;
			--warn: #b8860b;
			--warn-soft: #fdf6e3;
			--info: #2563eb;
			--info-soft: #eaf1ff;
			--radius: 10px;
			--radius-sm: 8px;
			--shadow: 0 20px 40px rgba(31, 36, 48, .10);
			--shadow-sm: 0 6px 16px rgba(31, 36, 48, .08);
		}
		* { box-sizing: border-box; }
		html, body { margin: 0; }
		body {
			min-height: 100vh;
			background: linear-gradient(135deg, var(--bg-from) 0%, var(--bg-to) 100%);
			background-attachment: fixed;
			color: var(--text);
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, ui-sans-serif, system-ui, sans-serif;
			line-height: 1.5;
			-webkit-font-smoothing: antialiased;
		}
		main { width: min(880px, 100%); margin: 0 auto; padding: 20px 14px 40px; }

		.topbar {
			display: flex; align-items: center; justify-content: space-between;
			gap: 12px; padding: 6px 4px 18px;
		}
		.brand { display: inline-flex; align-items: center; gap: 10px; font-weight: 700; font-size: 18px; color: var(--text); }
		.brand .logo {
			width: 34px; height: 34px; border-radius: 8px; flex: none;
			background: linear-gradient(135deg, var(--grad-from) 0%, var(--grad-to) 100%);
			display: inline-flex; align-items: center; justify-content: center;
			color: #fff; box-shadow: var(--shadow-sm);
		}
		.brand .logo svg { width: 20px; height: 20px; display: block; }
		.topbar .tag { color: var(--muted); font-size: 13px; }
		.icon { width: 18px; height: 18px; display: block; flex: none; }
		.alert .ic { display: inline-flex; }
		.alert .ic svg { width: 18px; height: 18px; display: block; }

		.card {
			background: var(--panel); border: 1px solid var(--line);
			border-radius: var(--radius); box-shadow: var(--shadow);
			overflow: hidden; margin-bottom: 16px;
		}
		.card-grad-header {
			background: linear-gradient(135deg, var(--grad-from) 0%, var(--grad-to) 100%);
			color: #fff; padding: 22px 22px;
		}
		.card-grad-header h1 { margin: 0; font-size: 22px; font-weight: 700; }
		.card-grad-header p { margin: 6px 0 0; opacity: .92; font-size: 14px; }
		.card-body { padding: 20px 22px; }
		.card-body + .card-body { border-top: 1px solid var(--line); }

		.sub-card {
			background: var(--panel-soft); border: 1px solid var(--line);
			border-radius: var(--radius-sm); padding: 16px;
		}

		.muted { color: var(--muted); }
		h2 { font-size: 16px; font-weight: 700; margin: 0 0 12px; }
		h3 { font-size: 15px; font-weight: 700; margin: 0 0 8px; }

		label { display: block; font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
		input[type=text], input:not([type]), input[type=email], input[type=number], select, textarea {
			width: 100%; border: 1px solid var(--line); border-radius: var(--radius-sm);
			padding: 12px 14px; background: #fff; color: var(--text); font: inherit;
			transition: border-color .15s, box-shadow .15s;
		}
		input:focus, select:focus, textarea:focus {
			outline: none; border-color: var(--accent);
			box-shadow: 0 0 0 3px rgba(102, 126, 234, .18);
		}
		.form-row { margin-bottom: 16px; }

		.btn {
			display: inline-flex; align-items: center; justify-content: center; gap: 8px;
			width: 100%; border: 1px solid transparent; border-radius: var(--radius-sm);
			padding: 13px 18px; font: inherit; font-weight: 700; cursor: pointer;
			background: linear-gradient(135deg, var(--grad-from) 0%, var(--grad-to) 100%);
			color: #fff; box-shadow: var(--shadow-sm); transition: transform .05s, opacity .15s;
		}
		.btn:hover { opacity: .94; }
		.btn:active { transform: translateY(1px); }
		.btn[disabled] { opacity: .6; cursor: not-allowed; }
		.btn.secondary { background: #fff; color: var(--text); border-color: var(--line); box-shadow: none; }

		.spinner {
			width: 16px; height: 16px; border-radius: 50%;
			border: 2px solid rgba(255,255,255,.5); border-top-color: #fff;
			animation: spin .7s linear infinite; display: inline-block;
		}
		.spinner.dark { border-color: rgba(102,126,234,.3); border-top-color: var(--accent); }
		@keyframes spin { to { transform: rotate(360deg); } }
		@keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .45; } }

		/* status badge */
		.badge {
			display: inline-flex; align-items: center; gap: 7px;
			border-radius: 999px; padding: 6px 12px; font-size: 13px; font-weight: 600;
			border: 1px solid transparent; white-space: nowrap;
		}
		.badge .dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; flex: none; }
		.badge--info    { background: var(--info-soft); color: var(--info); }
		.badge--accent  { background: #f0eefe; color: var(--grad-to); }
		.badge--ok      { background: var(--ok-soft); color: var(--ok); }
		.badge--danger  { background: var(--danger-soft); color: var(--danger); }
		.badge--warn    { background: var(--warn-soft); color: var(--warn); }
		.badge--muted   { background: #eef0f4; color: var(--muted); }
		.badge--pulse .dot { animation: pulse 1.2s ease-in-out infinite; }

		/* alert/banner */
		.alert {
			display: flex; gap: 10px; align-items: flex-start;
			border-radius: var(--radius-sm); padding: 13px 15px; font-size: 14px;
			border: 1px solid var(--line); background: var(--panel-soft);
		}
		.alert .ic { flex: none; font-size: 16px; line-height: 1.4; }
		.alert--info   { background: var(--info-soft); border-color: #d4e2ff; color: #1d4ed8; }
		.alert--accent { background: #f0eefe; border-color: #e0dbfb; color: #5b3fb0; }
		.alert--ok     { background: var(--ok-soft); border-color: #c7e8d5; color: #146c43; }
		.alert--danger { background: var(--danger-soft); border-color: #f5c6cd; color: #b02a37; }
		.alert--warn   { background: var(--warn-soft); border-color: #f0e2bd; color: #8a6500; }
		.alert--muted  { background: #eef0f4; border-color: #dde2ea; color: #4b5563; }

		/* detail rows */
		.details { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 18px; }
		.detail .k { font-size: 12px; text-transform: uppercase; letter-spacing: .03em; color: var(--muted); }
		.detail .v { font-weight: 600; word-break: break-word; }

		/* credential row */
		.cred {
			display: flex; align-items: center; gap: 10px;
			border: 1px solid var(--line); border-radius: var(--radius-sm);
			background: var(--panel-soft); padding: 10px 12px;
		}
		.cred .cred-main { min-width: 0; flex: 1; }
		.cred .cred-label { font-size: 12px; text-transform: uppercase; letter-spacing: .03em; color: var(--muted); margin-bottom: 2px; }
		.cred .cred-value { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 15px; word-break: break-all; }
		.copy-btn {
			flex: none; border: 1px solid var(--line); background: #fff; color: var(--accent);
			border-radius: 8px; padding: 8px 12px; font: inherit; font-size: 13px; font-weight: 600;
			cursor: pointer; transition: background .15s, color .15s, border-color .15s;
		}
		.copy-btn:hover { background: var(--panel-soft); }
		.copy-btn.copied { background: var(--ok-soft); color: var(--ok); border-color: #c7e8d5; }
		.field-hint { font-size: 13px; color: var(--muted); margin-top: 8px; }

		/* platform picker */
		.platforms { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
		.platform-opt { position: relative; }
		.platform-opt input { position: absolute; opacity: 0; pointer-events: none; }
		.platform-opt label {
			margin: 0; display: flex; align-items: center; gap: 10px; cursor: pointer;
			border: 1px solid var(--line); border-radius: var(--radius-sm);
			padding: 13px 14px; font-weight: 600; background: #fff; transition: all .15s;
		}
		.platform-opt input:checked + label {
			border-color: var(--accent); background: #f0eefe; color: var(--grad-to);
			box-shadow: 0 0 0 3px rgba(102, 126, 234, .14);
		}
		.platform-opt input:focus-visible + label { box-shadow: 0 0 0 3px rgba(102, 126, 234, .25); }
		.platform-opt .pf-ic { display: inline-flex; color: var(--accent); }
		.platform-opt input:checked + label .pf-ic { color: var(--grad-to); }
		.platform-opt .pf-ic svg { width: 20px; height: 20px; display: block; }

		/* accordion */
		.accordion summary {
			cursor: pointer; list-style: none; font-weight: 600;
			display: flex; align-items: center; justify-content: space-between; gap: 10px;
		}
		.accordion summary::-webkit-details-marker { display: none; }
		.accordion summary .chev { transition: transform .2s; color: var(--muted); }
		.accordion[open] summary .chev { transform: rotate(180deg); }
		.accordion .acc-body { margin-top: 12px; white-space: pre-wrap; color: var(--text); }

		.error { color: var(--danger); font-size: 13px; margin-top: 6px; }
		.hidden { display: none !important; }
		.stack > * + * { margin-top: 12px; }
		.foot { text-align: center; color: var(--muted); font-size: 13px; padding: 8px 4px 0; max-width: 280px; margin: 0 auto; }

		@media (min-width: 640px) {
			main { padding: 28px 18px 48px; }
		}
		@media (max-width: 560px) {
			.platforms { grid-template-columns: 1fr; }
			.details { grid-template-columns: 1fr; }
		}
		@media (max-width: 420px) {
			.topbar .tag { display: none; }
			.cred { flex-direction: column; align-items: stretch; }
			.copy-btn { width: 100%; }
		}
	</style>
</head>
<body>
	<main>
		<div class="topbar">
			<span class="brand">
				<span class="logo">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<rect x="2" y="6" width="20" height="12" rx="5"/>
						<line x1="6.5" y1="11" x2="9.5" y2="11"/><line x1="8" y1="9.5" x2="8" y2="12.5"/>
						<circle cx="15.5" cy="13" r=".6" fill="currentColor"/><circle cx="18" cy="11" r=".6" fill="currentColor"/>
					</svg>
				</span> Game Delivery</span>
			<span class="tag">Secure order access</span>
		</div>

		{{ $slot }}

		<p class="foot">Need help? <a href="https://difmark.com/en/profile/GlobalGames" target="_blank" rel="noopener noreferrer" style="color:var(--accent);font-weight:600;text-decoration:none;">Contact the seller.</a></p>
	</main>
</body>
</html>
