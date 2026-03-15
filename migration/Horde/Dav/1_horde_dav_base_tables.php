<?php

/**
 * Copyright 2013-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */

/**
 * Create Horde_Dav base tables.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */
class HordeDavBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade
     */
    public function up()
    {
        $t = $this->createTable('horde_dav_objects', ['autoincrementKey' => false]);
        $t->column('id_collection', 'string', ['null' => false]);
        $t->column('id_internal', 'string', ['limit' => 255, 'null' => false]);
        $t->column('id_external', 'string', ['limit' => 255, 'null' => false]);
        $t->end();

        $this->addIndex('horde_dav_objects', 'id_collection');
        $this->addIndex('horde_dav_objects', 'id_internal', ['unique' => true]);
        $this->addIndex('horde_dav_objects', 'id_external', ['unique' => true]);

        $t = $this->createTable('horde_dav_collections', ['autoincrementKey' => false]);
        $t->column('id_interface', 'string', ['limit' => 255, 'null' => false]);
        $t->column('id_internal', 'string', ['limit' => 255, 'null' => false]);
        $t->column('id_external', 'string', ['limit' => 255, 'null' => false]);
        $t->end();

        $this->addIndex('horde_dav_collections', 'id_interface');
        $this->addIndex('horde_dav_collections', 'id_internal');
        $this->addIndex('horde_dav_collections', 'id_external', ['unique' => true]);
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->dropTable('horde_dav_objects');
        $this->dropTable('horde_dav_collections');
    }
}
