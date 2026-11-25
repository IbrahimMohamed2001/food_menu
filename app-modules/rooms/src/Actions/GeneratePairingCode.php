<?php

namespace Modules\Rooms\Actions;

use Illuminate\Support\Str;
use Modules\Rooms\Models\DevicePairingCode;
use Modules\Rooms\Models\Room;

/**
 * Generates a new one-time pairing code for a room.
 *
 * The alphabet excludes look-alike characters: 0, O, 1, I.
 */
final class GeneratePairingCode
{
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    private const LENGTH = 8;

    private string $lastPlainCode = '';

    public function execute(Room $room, int $createdByUserId): DevicePairingCode
    {
        // Invalidate all previous unused codes for this room.
        $room->pairingCodes()
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->delete();

        $plainCode = $this->generateCode();

        // Remember the plain code so the caller can retrieve it once via plainCode().
        $this->lastPlainCode = $plainCode;

        return $room->pairingCodes()->create([
            'code_hash' => hash('sha256', $plainCode),
            'expires_at' => now()->addMinutes(15),
            'created_by_user_id' => $createdByUserId,
        ]);
    }

    /**
     * Return the plain code from the most recent execute() call.
     * Only call this immediately after execute().
     */
    public function plainCode(): string
    {
        return $this->lastPlainCode;
    }

    /**
     * Generate a random 8-character code from the safe alphabet.
     */
    public function generateCode(): string
    {
        $code = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $code;
    }

    /**
     * Normalize a user-submitted code: uppercase and strip dashes/spaces.
     */
    public static function normalizeCode(string $code): string
    {
        return strtoupper(str_replace(['-', ' '], '', $code));
    }

    /**
     * Format a code for display: insert a dash in the middle (XXXX-XXXX).
     */
    public static function formatCode(string $code): string
    {
        $code = strtoupper(str_replace(['-', ' '], '', $code));

        return substr($code, 0, 4).'-'.substr($code, 4);
    }
}
