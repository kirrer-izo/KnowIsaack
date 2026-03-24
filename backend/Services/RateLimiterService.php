<?php

namespace App\Services;

use App\Infrastructure\Database\RateLimitRepository;

class RateLimiterService {
    
    private $rateLimitRepository;
    private int $maxAttempts;
    private int $decayMinutes;

    public function __construct(RateLimitRepository $rateLimitRepository, int $maxAttempts = 5, int $decayMinutes = 60)
    {
        $this->rateLimitRepository = $rateLimitRepository;
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    // Returns true id the action is allowed, false if blocked
    public function attempt(string $identifier, string $action): bool
    {
        // 1. Clean old records (outside the decay window)
        $cutoff = date('Y-m-d H:i:s', strtotime("-$this->decayMinutes minutes"));
        $this->rateLimitRepository->deleteOld($identifier, $action, $cutoff);

        // 2. Find existing record
        $record = $this->rateLimitRepository->find($identifier, $action);

        // 3. No record → create new and allow
        if (!$record) {
            $this->rateLimitRepository->create($identifier, $action);
            return true;
        }

        // 4. If attempts exceed the maximum, check if still within the window
        if ($record['attempts'] >= $this->maxAttempts) {
            $firstAttempt = strtotime($record['first_attempt_at']);
            if (time() - $firstAttempt < $this->decayMinutes * 60) {
                // Still inside the window → block
                return false;
            }
            // Window passed → reset the record and allow (first attempt in new window)
            $this->rateLimitRepository->reset($identifier, $action);
            return true;
        }

        // 5. Attempts are below the limit → increment and allow
        $this->rateLimitRepository->increment($identifier, $action);
        return true;
    }
}