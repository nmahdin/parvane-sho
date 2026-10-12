<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * پاسخ یک شرکت‌کننده به یک پرسش چالش.
 */
#[Fillable(['challenge_question_id', 'participant_id', 'body'])]
class ChallengeQuestionAnswer extends Model
{
    /**
     * @return BelongsTo<ChallengeQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(ChallengeQuestion::class, 'challenge_question_id');
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
