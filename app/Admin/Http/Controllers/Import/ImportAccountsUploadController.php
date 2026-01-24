<?php

declare(strict_types=1);

namespace App\Admin\Http\Controllers\Import;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ImportAccountsUploadController
{
	public function __invoke(Request $request): RedirectResponse
	{
		$request->validate([
			'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
		]);

		$file = $request->file('file');
		if ($file === null) {
			return redirect()->back()->withErrors(['file' => 'Файл не загружен.']);
		}

		$content = file_get_contents($file->getRealPath());
		if ($content === false) {
			return redirect()->back()->withErrors(['file' => 'Не удалось прочитать файл.']);
		}

		$request->session()->put('import.csvText', $content);

		return redirect()->back();
	}
}
