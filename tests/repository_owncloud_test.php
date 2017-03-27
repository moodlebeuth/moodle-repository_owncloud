<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains tests for the repository_owncloud class.
 *
 * @package     repository_owncloud
 * @group       repository_owncloud
 * @copyright   2017 Westfälische Wilhelms-Universität Münster (WWU Münster)
 * @author      Projektseminar Uni Münster
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require($CFG->dirroot . '/repository/owncloud/lib.php');

class repository_owncloud_testcase extends advanced_testcase {

    /** @var null|repository_owncloud the repository_owncloud object, which the tests are run on. */
    private $repo = null;

    /**
     * Sets up the tested repository_owncloud object and all data records which are
     * needed to initialize the repository.
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        global $DB;

        // Setup some settings required for the Client.
        set_config('clientid', 'testid', 'tool_oauth2owncloud');
        set_config('secret', 'testsecret', 'tool_oauth2owncloud');
        set_config('server', 'localhost', 'tool_oauth2owncloud');
        set_config('path', 'owncloud/remote.php/webdav/', 'tool_oauth2owncloud');
        set_config('protocol', 'https', 'tool_oauth2owncloud');
        set_config('port', 1000, 'tool_oauth2owncloud');

        $typeparams = array('type' => 'owncloud', 'visible' => 0);

        // First, create a owncloud repository type and instance.
        $generator = $this->getDataGenerator()->get_plugin_generator('repository_owncloud');
        $reptype = $generator->create_type($typeparams);

        // Then insert a name for the instance into the database.
        $instance = $DB->get_record('repository_instances', array('typeid' => $reptype->id));
        $DB->update_record('repository_instances', (object) array('id' => $instance->id, 'name' => 'ownCloud'));

        // At last, create a repository_owncloud object from the instance id.
        $this->repo = new repository_owncloud($instance->id);
        $this->repo->options['typeid'] = $reptype->id;
    }

    /**
     * Checks the is_visible method in case the repository is set to visible in the database.
     */
    public function test_is_visible_parent_true() {
        // Check, if the method returns true, if the repository is set to visible in the database
        // and the client configuration data is complete.
        $this->assertTrue($this->repo->is_visible());

        // Check, if the method returns false, when the repository is set to visible in the database,
        // but the client configuration data is incomplete.
        $this->repo->options['success'] = false;

        $this->assertFalse($this->repo->is_visible());
    }

    /**
     * Checks the is_visible method in case the repository is set to hidden in the database.
     */
    public function test_is_visible_parent_false() {
        global $DB;
        $id = $this->repo->options['typeid'];

        // Check, if the method returns false, when the repository is set to visible in the database
        // and the client configuration data is complete.
        $DB->update_record('repository', (object) array('id' => $id, 'visible' => 0));

        $this->assertFalse($this->repo->is_visible());
    }

    /**
     * Helper method, which inserts a given owncloud mock object into the repository_owncloud object.
     *
     * @param $mock object mock object, which needs to be inserted.
     * @return ReflectionProperty the resulting reflection property.
     */
    protected function set_private_repository($mock) {
        $refclient = new ReflectionClass($this->repo);
        $private = $refclient->getProperty('owncloud');
        $private->setAccessible(true);
        $private->setValue($this->repo, $mock);

        return $private;
    }
}