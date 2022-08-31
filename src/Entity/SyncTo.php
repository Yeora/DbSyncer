<?php

declare(strict_types=1);

namespace Yeora\Entity;

use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Yeora\Helper\AskHelper;
use Yeora\Helper\OutputHelper;

class SyncTo extends Host
{
    protected ?string $type = 'SyncTo';

    /**
     * @param mixed[] $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * @param mixed[] $syncTos
     *
     * @return mixed[]
     */
    public static function getSyncToData(array $syncTos): array
    {
        $syncTosCount = count($syncTos);

        if ($syncTosCount === 0) {
            throw new RuntimeException('Missing SyncTo Data');
        }

        if ($syncTosCount > 1) {
            $syncTosQuestion = [];
            foreach ($syncTos as $syncTo) {
                $syncTosQuestion[] = implode(' ', $syncTo['credentials']);
            }

            $question = new ChoiceQuestion(
                'Please select a SyncTo. (Multiple selection possible by comma separation)',
                $syncTosQuestion,
                '0'
            );
            $question->setMultiselect(true);

            $selectedSyncTos = AskHelper::ask($question);

            $selectedSyncTosArray = [];
            foreach ($selectedSyncTos as $selectedSyncTo) {
                $selectedSyncTosArray[] = $syncTos[array_search($selectedSyncTo, $syncTosQuestion, true)];
            }

            return $selectedSyncTosArray;
        }

        if ($syncTosCount === 1) {
            $credentials = $syncTos[0]['credentials'] ?? null;
            OutputHelper::printComment(
                sprintf(
                    'Automatically selected SyncTo Host (Host=%s, Database=%s)',
                    $credentials['hostname'] ?? '',
                    $credentials['database'] ?? ''
                )
            );
        }

        return $syncTos;
    }
}