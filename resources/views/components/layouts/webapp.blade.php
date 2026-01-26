<!doctype html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="Expires" content="0">

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<script src="https://telegram.org/js/telegram-web-app.js"></script>

	<style>
		:root {
			--ah-bg: #0F1722;
			--ah-header: #28323A;
			--ah-panel: #182232;
			--ah-panel-2: #141C29;
			--ah-border: rgba(255, 255, 255, .10);
			--ah-input: rgba(255, 255, 255, .06);
			--ah-text: #E6EDF3;
			--ah-hint: rgba(230, 237, 243, .60);
			--ah-placeholder: rgba(230, 237, 243, .45);
			--ah-accent: #2481C9;
			--ah-accent-weak: rgba(36, 129, 201, .20);
			--ah-code: rgba(0, 0, 0, .55);
		}

		html, body {
			background: var(--ah-bg) !important;
			color: var(--ah-text) !important;
		}

		.app-shell {
			padding: 10px;
			padding-bottom: 80px;
		}

		.meta-bar {
			display: flex;
			justify-content: space-between;
			align-items: center;
			gap: 12px;
			color: var(--ah-hint);
			font-size: 12px;
			margin: 8px 0 10px;
		}

		.tabs-rail {
			position: relative;
			overflow: hidden;
			padding: 2px 10px 10px;
		}

		.tabs-wrap {
			overflow-x: auto;
			overflow-y: hidden;
			-webkit-overflow-scrolling: touch;
			scrollbar-width: none;
		}

		.tabs-wrap::-webkit-scrollbar {
			display: none;
		}

		.tabs-rail::before,
		.tabs-rail::after {
			content: '';
			position: absolute;
			top: 0;
			width: 18px;
			height: 44px;
			pointer-events: none;
			z-index: 5;
		}

		.tabs-rail::before {
			left: 0;
			background: linear-gradient(to right, var(--ah-bg), rgba(0, 0, 0, 0));
		}

		.tabs-rail::after {
			right: 0;
			background: linear-gradient(to left, var(--ah-bg), rgba(0, 0, 0, 0));
		}

		.nav-tabs {
			border: 0;
			flex-wrap: nowrap;
			gap: 8px;
		}

		.nav-tabs .nav-link {
			flex: 0 0 auto;
			border: 1px solid var(--ah-border);
			background: rgba(255, 255, 255, .04);
			color: var(--ah-text);
			border-radius: 10px;
			padding: 8px 12px;
			font-size: 14px;
			white-space: nowrap;
			line-height: 1.1;
		}

		.nav-tabs .nav-link.active {
			background: var(--ah-accent);
			border-color: var(--ah-accent);
			color: #fff;
		}

		.tab-content {
			background: transparent !important;
			border: 0 !important;
			padding: 0 !important;
		}

		.card-panel {
			background: var(--ah-panel);
			border: 1px solid var(--ah-border);
			border-radius: 12px;
			padding: 12px;
			margin-top: 10px;
		}

		.card-section {
			background: var(--ah-panel-2);
			border: 1px solid var(--ah-border);
			border-radius: 10px;
			padding: 12px;
			margin-bottom: 8px;
		}

		.form-label {
			display: none !important;
		}

		.form-control {
			border-radius: 8px !important;
			border: 1px solid var(--ah-border) !important;
			background: var(--ah-input) !important;
			color: var(--ah-text) !important;
			box-shadow: none !important;
		}

		.form-control::placeholder {
			color: var(--ah-placeholder) !important;
		}

		.form-control:focus {
			background: var(--ah-input) !important;
			border-color: rgba(36, 129, 201, .55) !important;
			box-shadow: 0 0 0 2px var(--ah-accent-weak) !important;
			outline: none !important;
		}

		.form-select {
			border-radius: 8px !important;
			border: 1px solid var(--ah-border) !important;
			background-color: var(--ah-input) !important;
			color: var(--ah-text) !important;
			box-shadow: none !important;
		}

		.form-select:focus {
			border-color: rgba(36, 129, 201, .55) !important;
			box-shadow: 0 0 0 2px var(--ah-accent-weak) !important;
			outline: none !important;
		}

		.history-controls .form-select {
			width: 86px;
		}

		.btn {
			border-radius: 8px !important;
		}

		.btn-spinner {
			width: 14px;
			height: 14px;
			border-radius: 999px;
			border: 2px solid rgba(255, 255, 255, .35);
			border-top-color: #fff;
			display: inline-block;
			animation: btnSpin 0.8s linear infinite;
		}

		@keyframes btnSpin {
			to { transform: rotate(360deg); }
		}

		.tab-header {
			text-align: center;
			padding-top: 12px;
			padding-bottom: 10px;
		}

		.tab-icon-wrap {
			width: 72px;
			height: 72px;
			border-radius: 999px;
			margin: 0 auto 12px;
			border: 1px solid var(--ah-border);
			background: rgba(255, 255, 255, .03);
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 26px;
		}

		.tab-title {
			font-size: 20px;
			font-weight: 700;
			margin: 0;
		}

		.tab-subtitle {
			margin-top: 6px;
			font-size: 12px;
			color: rgba(230, 237, 243, .55);
		}

		.pre-wrap {
			white-space: pre-wrap;
			word-break: break-word;
		}

		pre.codebox {
			white-space: pre-wrap;
			word-break: break-word;
			padding: 12px;
			border-radius: 10px;
			background: var(--ah-code);
			border: 1px solid var(--ah-border);
			margin: 0;
			font-size: 13px;
			line-height: 1.35;
			color: var(--ah-text);
		}

		.table {
			color: var(--ah-text);
			background: transparent;
		}

		.table thead th {
			border-color: var(--ah-border);
			color: var(--ah-hint);
			background: transparent;
		}

		.table tbody td {
			border-color: var(--ah-border);
			background: transparent;
		}

		.table-striped > tbody > tr:nth-of-type(odd) > td {
			background-color: rgba(255, 255, 255, .02);
		}

		.badge-muted {
			background: rgba(255, 255, 255, .08);
			border: 1px solid var(--ah-border);
			color: var(--ah-hint);
		}

		.list-stack {
			display: grid;
			gap: 10px;
		}

		.list-card {
			background: var(--ah-panel-2);
			border: 1px solid var(--ah-border);
			border-radius: 12px;
			padding: 12px;
		}

		.list-row {
			display: flex;
			justify-content: space-between;
			gap: 12px;
			font-size: 12px;
			line-height: 1.4;
			padding: 4px 0;
			border-bottom: 1px dashed rgba(255, 255, 255, .05);
		}

		.list-row:last-child {
			border-bottom: 0;
		}

		.list-label {
			color: var(--ah-hint);
		}

		.list-value {
			color: var(--ah-text);
			text-align: right;
			word-break: break-word;
		}

		.list-actions {
			margin-top: 10px;
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
		}

		.list-card code {
			color: var(--ah-text);
			background: rgba(255, 255, 255, .06);
			padding: 1px 6px;
			border-radius: 6px;
		}

		.list-empty {
			text-align: center;
			color: var(--ah-hint);
			padding: 18px 8px;
			background: var(--ah-panel-2);
			border: 1px dashed var(--ah-border);
			border-radius: 12px;
		}

		.chip {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			padding: 2px 8px;
			border-radius: 999px;
			background: var(--ah-accent-weak);
			color: var(--ah-text);
			font-size: 11px;
		}

		#copyright {
			color: var(--ah-hint);
			font-size: 11px;
		}
	</style>

	<title>{{ $title ?? 'AccessHub WebApp v2026-01-25-01' }}</title>
</head>
<body>
	{{ $slot }}
</body>
</html>
