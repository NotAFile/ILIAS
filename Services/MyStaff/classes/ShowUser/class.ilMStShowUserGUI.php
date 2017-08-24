<?php
/**
 * GUI-Class Table ilMStShowUserGUI
 *
 * @author Martin Studer <ms@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy ilMStShowUserGUI: ilMyStaffGUI
 * @ilCtrl_Calls ilMStShowUserGUI:ilFormPropertyDispatchGUI
 */
class ilMStShowUserGUI {

    /**
     * @var  ilTable2GUI
     */
    protected $table;
    /**
     * @var ilTabsGUI
     */
    protected $tabs;


	function __construct() {
		global $tpl, $ilCtrl, $lng, $ilTabs;

		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->tabs = $ilTabs;

		$this->usr_id = $_GET['usr_id'];
        $this->ctrl->setParameter($this, 'usr_id', $this->usr_id);
	}


    protected function checkAccessOrFail() {

        if(!$this->usr_id) {
            ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
            $this->ctrl->redirectByClass('ilPersonalDesktopGUI', "");
        }

        if (ilMyStaffAcess::getInstance()->hasCurrentUserAccessToMyStaff()) {
            return true;
        } else {
            ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
            $this->ctrl->redirectByClass('ilPersonalDesktopGUI', "");
        }
    }

	public function executeCommand() {
        $this->checkAccessOrFail();

        $this->addTabs('show_user');

		$cmd = $this->ctrl->getCmd();
		$next_class = $this->ctrl->getNextClass();

		switch($next_class) {
            case 'ilformpropertydispatchgui':
                $this->ctrl->setReturn($this,'index');
                $table = new ilMStShowUserCoursesTableGUI($this, 'index');
                $table->executeCommand();
                break;
			default:
				switch ($cmd) {
					case 'resetFilter':
					case 'applyFilter':
                    case 'index':
						$this->$cmd();
						break;
					default:
						$this->index();
						break;
				}
		}
	}

	public function index() {
		$this->listUsers();
	}

	public function listUsers() {
		$this->table = new ilMStShowUserCoursesTableGUI($this, 'index');
        $this->table->setTitle(sprintf($this->lng->txt('mst_courses_of'),ilObjCourse::_lookupTitle($this->usr_id)));

        $pub_profile = new ilPublicUserProfileGUI($this->usr_id);

        $tpl = new ilTemplate('./Services/MyStaff/templates/default/tpl.show_user_container.html', true, true);

        $tpl->setCurrentBlock('courses');
        $tpl->setVariable('COURSES',$this->table->getHTML());
        $tpl->parseCurrentBlock();

        $tpl->setCurrentBlock('profile');
        $tpl->setVariable('PROFILE',$pub_profile->getEmbeddable());
        $tpl->parseCurrentBlock();

        $this->tpl->setContent($tpl->get());
	}


	public function applyFilter() {
        $this->table = new ilMStShowUserCoursesTableGUI($this, 'applyFilter');
        $this->table->writeFilterToSession();
		$this->table->resetOffset();
		$this->index();
	}


	public function resetFilter() {
        $this->table = new ilMStShowUserCoursesTableGUI($this, 'resetFilter');
		$this->table->resetOffset();
		$this->table->resetFilter();
		$this->index();
	}

    public function getId() {
        $this->table = new ilMStShowUserCoursesTableGUI($this, 'index');
        return $this->table->getId();
    }

	public function cancel() {
		$this->ctrl->redirect($this);
	}

    public function addTabs($active_tab_id) {
	    $this->tabs->setBackTarget($this->lng->txt('mst_list_users'), $this->ctrl->getLinkTargetByClass(array("ilMyStaffGUI","ilMStListUsersGUI")));
        $this->tabs->addTab('show_user', $this->lng->txt('mst_show_courses'), $this->ctrl->getLinkTargetByClass(array("ilMyStaffGUI","ilMStShowUserGUI"), 'index'));

        if($active_tab_id) {
            $this->tabs->activateTab($active_tab_id);
        }
    }
}