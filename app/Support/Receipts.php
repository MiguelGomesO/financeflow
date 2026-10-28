<?php

namespace App\Support;

use App\Models\Transaction;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Comprovantes das transações. Ficam no disco privado e só saem pela rota
 * "receipts.show", que confere de quem é a transação.
 */
class Receipts
{
    public const MAX_KB = 5120;

    public const MIMES = 'jpg,jpeg,png,webp,pdf';

    public static function disk(): Filesystem
    {
        return Storage::disk('local');
    }

    public static function store(UploadedFile $file): string
    {
        return static::disk()->putFile('receipts', $file);
    }

    /**
     * Apaga o arquivo se nenhuma transação (de qualquer usuário) ainda o usa.
     * Parcelas da mesma compra compartilham um comprovante só.
     */
    public static function releaseIfUnused(?string $path): void
    {
        if ($path !== null && ! Transaction::withoutGlobalScopes()->where('attachment_path', $path)->exists()) {
            static::disk()->delete($path);
        }
    }
}
