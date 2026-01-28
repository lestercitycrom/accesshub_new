<?php

declare(strict_types=1);

return [
	/*
	 * Field mapping for CSV import.
	 * Maps internal field names to possible CSV column names (synonyms).
	 * The first name in each array is the primary/canonical name.
	 */
	'field_mapping' => [
		'game_name' => [
			'Game Name',
			'game', // Legacy support for old CSV format
		],
		'platform' => [
			'Game Name .1',
			'Platform',
			'platform', // Legacy support for old CSV format
		],
		'console_account_login' => [
			'Console Account Login',
			'Login',
			'login', // Legacy support for old CSV format
		],
		'console_account_password' => [
			'Console Account Password',
			'Password',
			'password', // Legacy support for old CSV format
		],
		'mail_account_login' => [
			'Mail Account Login',
		],
		'mail_account_password' => [
			'Mail Account Password',
		],
		'two_fa_mail_account_date' => [
			'2-fa Mail Account Date',
		],
		'recover_code' => [
			'Recover Code',
		],
		'comment' => [
			'Comment',
		],
	],

	/*
	 * Required fields for import.
	 * At least these fields must be present in CSV header.
	 */
	'required_fields' => [
		'game_name',
		'platform',
		'console_account_login',
		'console_account_password',
	],
];
