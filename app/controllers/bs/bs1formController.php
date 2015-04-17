<?php
/**
 * Created by PhpStorm.
 * User: attapon.th
 * Date: 16/2/2558
 * Time: 13:33
 */


namespace EThesis\Controllers\Bs;

use \EThesis\Library\Form AS Form;

class bs1formController extends \Phalcon\Mvc\Controller
{


    var $init_class;

    private $_lang = 'th';


    public function initialize()
    {
        $this->lang_class = new \EThesis\Library\Lang();
        $this->_lang = $this->session->get('lang');
        if ($this->session->get('auth') !== TRUE) {
            die('false');
        }
        //print_r($this->init_class[sess]->get());
    }

    public function indexAction()
    {

//        $form = $this->_get_config();
//        echo '<pre>';
//        print_r(array_keys($form->get_form()['input']));
//        echo '</pre>';
//        return;
        $this->view->enable();
        $form = $this->_get_config();
        $this->view->setVars($form->get_form());
        $this->view->pick('/bs/bs1_form_00');
        $this->logs->set(LOG_OPEN_PAGE);

    }


    public function datapersonAction()
    {
        $his_person_model = new \EThesis\Models\Hrd\Hrd_person_model();
        $his_education_model = new \EThesis\Models\Hrd\Hrd_education_model();
        $pk_id = $_POST['id'];
        $result = $his_person_model->select_by_filter([], ['IN_ID' => $pk_id]);

        if (is_object($result) && $result->RecordCount() > 0) {
            $row = $result->FetchRow();
            $data = [
                'FACULTY_ID' => $row['REG_FACULTY_ID'],
                'HRD_FACULTY_ID' => $row['FACULTY_ID'],
                'PROGRAM_ID' => $row['REG_PROGRAM_ID'],
                'CITIZEN_ID' => $row['CITIZEN_ID'],
                'TITLE_ID' => $row['TITLE_ID'],
                'FNAME_TH' => $row['FNAME_TH'],
                'LNAME_TH' => $row['LNAME_TH'],
                'POS_ACADEMIC_ID' => $row['POS_ACAD_ID'],
                'POS_EXECUTIVE_ID' => $row['POS_EXEC_ID'],
                'TEL_PERSONNEL' => $row['MOBILE_PHONE'],
                'CK_POSITION_ID' => $row['POS_ID'],
            ];

            $result = $his_education_model->select_by_filter([], ['PERSON_ID' => $pk_id, 'HIGHEST_DEGREE' => 'T']);
            if (is_object($result) && $result->RecordCount() > 0) {
                $row = $result->FetchRow();
                $data['MAX_DEGREE_ID'] = ($row['LEV_ID'] == '80' ? 4 :
                    ($row['LEV_ID'] == '60' ? 3 :
                        ($row['LEV_ID'] == '40' ? 2 : '')
                    )
                );
                $data['MAX_COURSE_NAME_TH'] = $row['QUA_NAME_TH'];
                $data['MAX_COURSE_NAME_EN'] = $row['QUA_NAME_EN'];

                $data['MAX_PROGRAM_NAME_TH'] = $row['MAJOR_NAME_TH'];
                $data['MAX_PROGRAM_NAME_EN'] = $row['MAJOR_NAME_EN'];

                $data['MAX_UNIVERSITY_NAME_TH'] = $row['EDUC_NAME_TH'];
                $data['MAX_UNIVERSITY_NAME_EN'] = $row['EDUC_NAME_EN'];

                $data['MAX_COUNTRY_NAME_TH'] = $row['COUNTRY_NAME_TH'];
                $data['MAX_COUNTRY_NAME_EN'] = $row['COUNTRY_NAME_EN'];

            }

            echo json_encode($data);
        }
    }

    public function setdataAction($set = '')
    {
//        print_r($_POST);return;
        if (!empty($set)) {
            $data = $_POST;
            $bs1_master = [];
            $bs1_award = [];
            $bs1_research = [];
            $form = $this->_get_config();

            //Set Data
            //Data Model Bs1_Master
            foreach ($data as $key => $val) {
                if (!in_array($key, ['MORE_RESEARCH_NAME', 'AWARD_NAME', 'AWARD_YEAR'])) {
                    if (is_array($val)) {
                        $bs1_master[$key] = implode(',', $val);
                    } else {
                        $bs1_master[$key] = $val;

                    }
                }
            }
//            print_r($bs1_master);
            if ($set == 'add') {
//                print_r(implode(',',array_keys($bs1_master)));
                $bs1_model = new \EThesis\Models\Bs\Bs1_master_model();
//                $bs1_model->adodb->debug = true;
                $bs1_model->adodb->BeginTrans();
                $result = $bs1_model->insert($bs1_master);
//                print_r($result);
                $ok = $result && true;
                if ($ok) {
                    $bs1_id = $bs1_model->get_last_id();
//                    print_r($bs1_id);
                    //Data Model BS1_Research
                    $research_model = new \EThesis\Models\Bs\Bs1_research_model();
                    foreach ($data['MORE_RESEARCH_NAME'] as $val) {
                        $ok = $ok && $research_model->insert([
                                'BS1_ID' => $bs1_id,
                                'BS1_RESEARCH_NAME_TH' => $val,
                                'BS1_RESEARCH_NAME_EN' => $val
                            ]);
                    }
                    //Data Model BS1_Award
                    $award_model = new \EThesis\Models\Bs\Bs1_award_model();
                    foreach ($data['AWARD_NAME'] as $i => $val) {
                        $ok = $ok && $award_model->insert([
                                'BS1_ID' => $bs1_id,
                                'BS1_AWARD_NAME_TH' => $val,
                                'BS1_AWARD_NAME_EN' => $val,
                                'BS1_AWARD_YEAR' => $data['AWARD_YEAR'][$i]
                            ]);
                    }
                }
                $bs1_model->adodb->CommitTrans($ok);
                echo $form->set_responce($set, $ok);
            }
        }
    }


    private function _get_config()
    {
        $form = new Form();
        $form->param_default['col'] = 12;

        $form->set_urlset($this->url->get('bs/bs1form/setdata/add/'));
        $form->set_model(new \EThesis\Models\Bs\Bs1_master_model());

        $form->param_default['required'] = true;


        $form->add_input('OK', [
            'type' => Form::TYPE_SUBMIT,
        ]);
        $form->add_input('RESET', [
            'type' => Form::TYPE_RESET,
        ]);

        /*
         * ประวัติส่วนหัว
         */
        $form->add_input('PERSON_IMAGE', [
            'type' => Form::TYPE_FILE,
            'filesize' => 2,
            'filetype' => 'image',
//            'novalidate' => true,
        ]);

        $form->add_input('ACAD_YEAR', [
            'type' => Form::TYPE_NUMBER,
            'maxlength' => 4,
            'minlength' => 4,
            'value' => 2558,
        ]);
        $form->add_input('ASEAN_STATUS', [
            'type' => Form::TYPE_RADIO,
            'datalang' => 'ASEAN_STATUS',
            'value' => 'T',
        ]);
        $form->add_input('ADVISER_STATUS', [
            'type' => Form::TYPE_RADIO,
            'datalang' => 'ADVISER_STATUS',
        ]);
        $form->add_input('FACULTY_ID', [
            'type' => Form::TYPE_SELECT,
            'datamodel' => 'MAS_FACULTY',
        ]);
        $form->add_input('PERSON_ID', [
            'type' => Form::TYPE_AUTOCOMPLETE,
            'datamodel' => 'HRD_HIS_INSTRUCTOR',
            'maxitem' => 1,
            'required' => false,
        ]);
        $form->add_input('PROGRAM_ID', [
            'type' => Form::TYPE_SELECT,
            'datamodel' => 'MAS_PROGRAM',
            'select_filter' => 'FACULTY_ID',
        ]);
        $form->add_input('CITIZEN_ID', [
            'type' => Form::TYPE_TEXT,
            'citizen' => true,
        ]);

        /*
         * 1.  ประวัติส่วนตัว
         */
        $form->add_input('TITLE_ID', [
            'type' => Form::TYPE_SELECT,
            'datamodel' => 'HRD_TITLE',
            'label' => 'ชื่อ - สกุล',
        ]);
        $form->add_input('FNAME_TH', [
            'type' => Form::TYPE_TEXT,
            'holder' => $this->lang_class->label_manual('ชื่อ', 'First Name')
        ]);
        $form->add_input('LNAME_TH', [
            'type' => Form::TYPE_TEXT,
            'holder' => $this->lang_class->label_manual('สกุล', 'Last Name')

        ]);
        $form->add_input('POS_EXECUTIVE_ID', [
            'type' => Form::TYPE_SELECT,
            'datamodel' => 'HRD_POS_EXEC',
            'required' => false,
        ]);
        $form->add_input('POS_ACADEMIC_ID', [
            'type' => Form::TYPE_SELECT,
            'datamodel' => 'HRD_POS_ACAD'
        ]);
        $form->add_input('HRD_FACULTY_ID', [
            'type' => Form::TYPE_SELECT,
            'datamodel' => 'HRD_FACULTY',
        ]);
        $form->add_input('UNIVERSITY_NAME', [
            'type' => Form::TYPE_TEXT,
            'value' => 'มหาวิทยาลัยพะเยา'
        ]);
        $form->add_input('TEL_PERSONNEL', [
            'type' => Form::TYPE_TEXT,
            'minlength' => 9
        ]);
        $form->add_input('TEL_WORK', [
            'type' => Form::TYPE_TEXT,
            'minlength' => 4,
            'required' => false,
        ]);
        $form->add_input('TEL_WORK_NEXT', [
            'type' => Form::TYPE_TEXT,
            'minlength' => 4,
            'required' => false,
        ]);
        /*
         * 2.  ข้อมูลคถณวุฒิสูงสุด
         */
        $form->add_input('MAX_DEGREE_ID', [
            'type' => Form::TYPE_RADIO,
            'datalang' => 'MAX_DEGREE_ID',
        ]);

        $form->add_input('MAX_GRADUATE_DATE', [
            'type' => Form::TYPE_DATE,
        ]);
        $form->add_input('MAX_COURSE_NAME_TH', [
            'type' => Form::TYPE_TEXT,
        ]);
        $form->add_input('MAX_PROGRAM_NAME_TH', [
            'type' => Form::TYPE_TEXT,
        ]);
        $form->add_input('MAX_UNIVERSITY_NAME_TH', [
            'type' => Form::TYPE_TEXT,
        ]);
        $form->add_input('MAX_COUNTRY_NAME_TH', [
            'type' => Form::TYPE_TEXT,
        ]);
        $form->add_input('MAX_COURSE_NAME_EN', [
            'type' => Form::TYPE_TEXT,
        ]);
        $form->add_input('MAX_PROGRAM_NAME_EN', [
            'type' => Form::TYPE_TEXT,
        ]);
        $form->add_input('MAX_UNIVERSITY_NAME_EN', [
            'type' => Form::TYPE_TEXT,
        ]);
        $form->add_input('MAX_COUNTRY_NAME_EN', [
            'type' => Form::TYPE_TEXT,
        ]);

        /*
         * 3.  ประสบการณ์การสอน หรือความเชี่ยวชาญ
         */
        $form->add_input('BS1_EXPERIENCE', [
            'type' => Form::TYPE_TEXTAREA,
            'textarea_rows' => 10,
            'col' => 12,
        ]);

        /*
        * 4.  ผลงานทางวิชาการ
        */
        $form->add_input('PRESENT_ACADEMIC_FOR_GRADUATE', [
            'type' => Form::TYPE_RADIO,
            'data' => ['F' => 'ไม่มี', 'T' => 'มี'],
            'label' => 'และที่มิใช่ส่วนหนึ่งของการศึกษาเพื่อรับปริญญา',
        ]);
        $form->add_input('PRESENT_ACADEMIC_TYPE', [
            'type' => Form::TYPE_CHECKBOX,
            'data' => ['M' => 'ตีพิมพ์ในวารสาร', 'A' => 'เสนอต่อที่ประชุมวิชาการ'],
            'novalidate' => true,
            'required' => false,
        ]);
        $form->add_input('PRESENT_ACADEMIC_YEAR', [
            'type' => Form::TYPE_NUMBER,
            'maxlength' => 4,
            'minlength' => 4,
            'novalidate' => true,
        ]);
        $form->add_input('PRESENT_ACADEMIC_TYPE_NAME', [
            'type' => Form::TYPE_TEXT,
            'novalidate' => true,
            'label' => 'ชื่อวารสาร/การประชุมวิชาการ'
        ]);
        $form->add_input('PRESENT_ACADEMIC_PLACE_NAME', [
            'type' => Form::TYPE_TEXT,
            'novalidate' => true,
            'label' => 'สถานที่'
        ]);
        $form->add_input('PRESENT_ACADEMIC_PROVINCE_NAME', [
            'type' => Form::TYPE_TEXT,
            'novalidate' => true,
            'label' => $this->lang_class->label('PROVINCE')
        ]);
        $form->add_input('PRESENT_ACADEMIC_COUNTRY_NAME', [
            'type' => Form::TYPE_TEXT,
            'novalidate' => true,
            'label' => $this->lang_class->label('COUNTRY')
        ]);
        $form->add_input('PRESENT_ACADEMIC_TITLE_ID', [
            'type' => Form::TYPE_SELECT,
            'datamodel' => 'MAS_TITLE',
            'novalidate' => true,
            'label' => 'ชื่อ - สกุล เจ้าของผลงาน',
        ]);
        $form->add_input('PRESENT_ACADEMIC_FNAME_TH', [
            'type' => Form::TYPE_TEXT,
            'novalidate' => true,
            'holder' => $this->lang_class->label_manual('ชื่อ', 'First Name')
        ]);
        $form->add_input('PRESENT_ACADEMIC_LNAME_TH', [
            'type' => Form::TYPE_TEXT,
            'novalidate' => true,
            'holder' => $this->lang_class->label_manual('สกุล', 'Last Name')

        ]);
        $form->add_input('PRESENT_ACADEMIC_NAME', [
            'type' => Form::TYPE_TEXT,
            'novalidate' => true,
            'label' => $this->lang_class->label_manual('ชื่อเรื่อง')

        ]);
        $form->add_input('PRESENT_ACADEMIC_IS_EXPERIENCE', [
            'type' => Form::TYPE_RADIO,
            'label' => 'ประสบการณ์การเป็นอาจารย์ที่ปรึกษาการศึกษาค้นคว้าด้วยตนเอง',
            'data' => ['F' => 'ไม่มี', 'T' => 'มี'],
        ]);
        $form->add_input('PRESENT_ACADEMIC_IS_EXPERIENCE_YEAR', [
            'type' => Form::TYPE_NUMBER,
            'maxlength' => 4,
            'minlength' => 4,
            'novalidate' => true,
            'label' => 'ระบุครั้งล่าสุด พ.ศ.'
        ]);

        /*
         * 5
         */
        $form->add_input('MORE_RESEARCH_STATUS', [
            'type' => Form::TYPE_RADIO,
            'label' => 'งานวิจัยที่สนใจหรือกำลังดำเนินการ',
            'data' => ['F' => 'ไม่มี', 'T' => 'มี'],
        ]);
        $form->add_input('MORE_RESEARCH_NAME[]', [
            'type' => Form::TYPE_TEXT,
            'label' => '5.1',
            'novalidate' => true,
        ]);
//        $form->add_input('MORE_RESEARCH_NAME[1]', [
//            'type' => Form::TYPE_TEXT,
//            'label' => '5.2',
//            'required' => false,
//        ]);

        /*
        * 6
        */
        $form->add_input('AWARD_NAME_STATUS', [
            'type' => Form::TYPE_RADIO,
            'label' => 'รางวัลหรือเกียรติคุณทางการสอน การวิจัยหรือทางวิชาการ ที่เคยได้รับ',
            'data' => ['F' => 'ไม่มี', 'T' => 'มี'],
        ]);
        $form->add_input('AWARD_NAME[]', [
            'type' => Form::TYPE_TEXT,
            'label' => '6.1',
            'novalidate' => true,
        ]);
        $form->add_input('AWARD_YEAR[]', [
            'type' => Form::TYPE_NUMBER,
            'label' => 'ปีที่ได้รับ',
            'novalidate' => true,
        ]);
//        $form->add_input('AWARD_NAME[1]', [
//            'type' => Form::TYPE_TEXT,
//            'required' => false,
//            'label' => '6.2',
//        ]);
//        $form->add_input('AWARD_YEAR[1]', [
//            'type' => Form::TYPE_NUMBER,
//            'required' => false,
//            'label' => 'ปีที่ได้รับ',
//        ]);

        /*
         *
         *  7
         *
         */
        $form->add_input('AWARD_NAME_STATUS', [
            'type' => Form::TYPE_RADIO,
            'label' => 'รางวัลหรือเกียรติคุณทางการสอน การวิจัยหรือทางวิชาการ ที่เคยได้รับ',
            'data' => ['F' => 'ไม่มี', 'T' => 'มี'],
        ]);


        $form->add_input('ADVISER_TYPE_ID', [
            'type' => Form::TYPE_RADIO,
            'datalang' => 'ADVISER_TYPE_ID'
        ]);
        $form->add_input('CK_POSITION_ID', [
            'type' => Form::TYPE_SELECT,
            'label' => 'ตรวจสอบแล้วเป็นพนังงานมหาวิทยาลัยตามสัญญาจ้างตำแหน่ง ',
            'required' => false,
            'datamodel' => 'HRD_POSITION'
        ]);

        $form->add_input('CK_START_DATE', [
            'type' => Form::TYPE_DATE,
            'required' => false,
            'label' => 'ตั้งแต่วันที่'
        ]);
        $form->add_input('CK_END_DATE', [
            'type' => Form::TYPE_DATE,
            'required' => false,
            'label' => 'ถึงวันที่'
        ]);
        $form->add_input('PERSON_CONTRACT_FILE', [
            'type' => Form::TYPE_FILE,
            'filesize' => 10,
            'filetype' => 'pdf',
            'label' => 'ไฟล์สัญญาจ้าง (PDF)'
        ]);



        $form->add_input('POP_INS_ID', [
            'type' => Form::TYPE_RADIO,
            'datalang' => 'POP_INS_ID',
            'required' => false,
        ]);
        $form->add_input('POP_HEAD_THESIS_ID', [
            'type' => Form::TYPE_RADIO,
            'required' => false,
            'datalang' => 'POP_HEAD_THESIS_ID'
        ]);
        $form->add_input('POP_COM_THESIS_ID', [
            'type' => Form::TYPE_RADIO,
            'required' => false,
            'datalang' => 'POP_COM_THESIS_ID'
        ]);
        $form->add_input('POP_INS_IS_ID', [
            'type' => Form::TYPE_RADIO,
            'required' => false,
            'datalang' => 'POP_INS_IS_ID'
        ]);
        $form->add_input('POP_THESIS_ID', [
            'type' => Form::TYPE_CHECKBOX,
            'required' => false,
            'datalang' => 'POP_THESIS_ID'
        ]);

        return $form;


    }

} 