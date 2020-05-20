<?php

namespace Watson\Workflows\YFormEmailTemplate;

use Watson\Foundation\Command;
use Watson\Foundation\Documentation;
use Watson\Foundation\Result;
use Watson\Foundation\ResultEntry;
use Watson\Foundation\Watson;
use Watson\Foundation\Workflow;

class YFormEmailTemplateSearch extends Workflow
{
    /**
     * Provide the commands of the search.
     *
     * @return array
     */
    public function commands()
    {
        return ['yfe'];
    }

    /**
     * @return Documentation
     */
    public function documentation()
    {
        $documentation = new Documentation();
        $documentation->setDescription(Watson::translate('watson_yfe_documentation_description'));
        $documentation->setUsage('yfe keyword');
        $documentation->setExample('yfe Phrase');

        return $documentation;
    }

    /**
     * Return array of registered page params.
     *
     * @return array
     */
    public function registerPageParams()
    {
        return [];
    }

    /**
     * Execute the command for the given Command.
     *
     * @param Command $command
     *
     * @return Result
     */
    public function fire(Command $command)
    {
        $result = new Result();

        $fields = ['name', 'mail_from', 'mail_from_name', 'mail_reply_to', 'mail_reply_to_name', 'subject', 'body', 'body_html', 'attachments'];

        $sql_query = '
       SELECT      * 
       FROM       ' . Watson::getTable('yform_email_template') . ' 
       WHERE       ' . $command->getSqlWhere($fields) . ' 
       ORDER BY id DESC';

        $items = $this->getDatabaseResults($sql_query);

        if (count($items)) {
            $counter = 0;

            foreach ($items as $item) {
                $url = Watson::getUrl(['page' => 'yform/email/index', 'base_path' => 'yform/email/index', 'template_id' => $item['id'], 'func' => 'edit']);

                ++$counter;
                $entry = new ResultEntry();
                if ($counter == 1) {
                    $entry->setLegend('YFormEmailTemplate');
                }

                if (isset($item['subject'])) {
                    $entry->setValue($item['subject'] . ' (' . $item['id'] . ')');
                } else {
                    $entry->setValue($item['id']);
                }
                $entry->setIcon('fa-envelope-o');
                $entry->setUrl($url);
                $entry->setQuickLookUrl($url);

                $result->addEntry($entry);
            }
        }
        return $result;
    }
}
