<?php

declare(strict_types=1);

namespace Yeora\Entity;


use Exception;
use JsonException;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Yeora\Helper\AskHelper;
use Yeora\Helper\InputHelper;
use Yeora\Helper\OutputHelper;

class SyncFrom extends Host
{
    protected ?string $type = 'SyncFrom';

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * @param mixed[] $syncFroms
     *
     * @return mixed|null
     */
    public static function getSyncFromData(array $syncFroms)
    {
        $syncFromsCount = count($syncFroms);

        if ($syncFromsCount === 0) {
            throw new RuntimeException('Missing SyncFrom Data');
        }

        if ($syncFromsCount > 1) {
            $syncFromsQuestion = [];

            foreach ($syncFroms as $syncTo) {
                $syncFromsQuestion[] = implode(' ', $syncTo['credentials']);
            }

            $question = new ChoiceQuestion(
                'Please select a SyncFrom. Multiple selection not possible',
                $syncFromsQuestion,
                '0'
            );

            $question->setMultiselect(false);

            return $syncFroms[array_search(AskHelper::ask($question), $syncFromsQuestion, true)];
        }

        if ($syncFromsCount === 1) {
            $credentials = $syncFroms[0]['credentials'] ?? null;
            OutputHelper::printComment(
                sprintf(
                    'Automatically selected SyncFrom Host (Host=%s, Database=%s)',
                    $credentials['hostname'] ?? '',
                    $credentials['database'] ?? ''
                )
            );
        }

        return $syncFroms[0] ?? null;
    }

}