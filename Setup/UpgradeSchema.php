<?php

namespace Swissup\Marketplace\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->createJobTable($setup);
        }

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $this->addSignatureToJobTable($setup);
        }

        $setup->endSetup();
    }

    protected function createJobTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable('swissup_marketplace_job'))
            ->addColumn(
                'job_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'primary'  => true,
                    'unsigned' => true,
                    'identity' => true,
                ],
                'ID'
            )
            ->addColumn(
                'cron_schedule_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => true,
                    'unsigned' => true,
                ],
                'Cron Schedule ID'
            )
            ->addColumn(
                'class',
                Table::TYPE_TEXT,
                255,
                [],
                'Job Class'
            )
            ->addColumn(
                'arguments_serialized',
                Table::TYPE_TEXT,
                '2M',
                [],
                'Job Arguments'
            )
            ->addColumn(
                'status',
                Table::TYPE_SMALLINT,
                null,
                [
                    'nullable' => false,
                    'default'  => 0
                ],
                'Job Status'
            )
            ->addColumn(
                'visibility',
                Table::TYPE_SMALLINT,
                null,
                [
                    'nullable' => false,
                    'default'  => 1
                ],
                'Job Visibility'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Create Time'
            )
            ->addColumn(
                'scheduled_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true, 'default' => null],
                'Schedule Time'
            )
            ->addColumn(
                'started_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true, 'default' => null],
                'Start Time'
            )
            ->addColumn(
                'finished_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true, 'default' => null],
                'Finish Time'
            )
            ->addColumn(
                'attempts',
                Table::TYPE_SMALLINT,
                null,
                [
                    'nullable' => false,
                    'default'  => 0
                ],
                'Attempts'
            )
            ->addColumn('output', Table::TYPE_TEXT);

        $setup->getConnection()->createTable($table);
    }

    protected function addSignatureToJobTable(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('swissup_marketplace_job'),
            'signature',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 64,
                'comment' => 'Signature',
            ]
        );
    }
}
