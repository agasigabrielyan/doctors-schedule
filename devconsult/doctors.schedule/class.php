<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Класс находит графики работы и всех пользователей привязанных к ним
 * Выводит в шаблоне компонента
 *
 * info@dev-consult.ru
 * dev-consult.ru
 * version 1.0
 */
use Bitrix\Main\Loader;
use Bitrix\Crm\Entity\Deal;
use Bitrix\Main\Engine\Action;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\Form\Schedule\ScheduleForm;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;

class DoctorsSchedule extends \CBitrixComponent
{
    /**
     * method returns users and their worksdays
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function getDoctorsWithWorksDays() {
        $workdaysOfDoctors = [];

        \CModule::IncludeMOdule('timeman');

        // находим рабочие графики
        $scheduleList = ScheduleTable::getList([
            'select' => ['ID']
        ])->fetchAll();

        $scheduleList = array_column($scheduleList, 'ID');

        $allWorktimeData = [];
        $allUsers = [];
        foreach ($scheduleList as $scheduleId) {
            $scheduleRepository = DependencyManager::getInstance()->getScheduleRepository();
            $schedule = $scheduleRepository->findByIdWith($scheduleId, [
                'SHIFTS',  'USER_ASSIGNMENTS'
            ]);

            $provider = DependencyManager::getInstance()->getScheduleProvider();
            $users = $provider->findActiveScheduleUserIds($schedule);
            $scheduleForm = new ScheduleForm($schedule);

            $shiftTemplate = new \Bitrix\Timeman\Form\Schedule\ShiftForm();
            $shiftFormWorkDays = [];
            foreach (array_merge([$shiftTemplate], $scheduleForm->getShiftForms()) as $shiftIndex => $shiftForm)
            {
                $shiftFormWorkDays[] = array_map('intval', str_split($shiftForm->workDays));
            }

            $worktime = [];
            foreach ($users as $userId)
            {
                foreach($shiftFormWorkDays as $key => $value) {
                    if( $value[0] !== 0 ) {
                        $worktime[$userId] = $value;
                    }
                }
            }

            $allWorktimeData[] = $worktime;
        }

        $activeWorkTimeData = [];
        foreach ( $allWorktimeData as $key => $workTime ) {
            if( count($workTime) > 0 ) {
                foreach ( $workTime as $workTimeKey => $workTimeValue ) {
                    $activeWorkTimeData[$workTimeKey] = $workTimeValue;
                }
            }
        }

        $weekDaysNames = [
            '1' => 'Пн',
            '2' => 'Вт',
            '3' => 'Ср',
            '4' => 'Чт',
            '5' => 'Пт',
            '6' => 'Сб',
            '7' => 'Вс',
        ];

        $weekDaysResult = $this->getSixDaysFromCurrentDay();
        $weekDays = [];
        foreach ( $weekDaysResult as $value) {
            $weekDays[$value['WEEK_DAY']] = [
                'WEEK_DAY' => $weekDaysNames[$value['WEEK_DAY']],
                "D_DAY" => $value['D_DAY']
            ];
        }

        $dayofweek = date('w', strtotime( date('Y-m-d') ));

        foreach ( $activeWorkTimeData  as $activeWorkTimeKey => $aktiveWorkTimeValue ) {

            $weekHtmlOutput = "<div class='doctor-schedule'>";
            foreach ( $weekDays as $key => $value ) {
                $class = "";
                // определим является ли день рабочим или выходным и зададим нужный класс
                if( in_array( $key, $aktiveWorkTimeValue ) ) {
                    $class = "doctor-schedule__workday";
                    $title = "Рабочий день";
                } else {
                    $class = "doctor-schedule__holiday";
                    $title = "Выходной";
                }

                // определим является ли день сегодняшним и добавим класс если это так
                if( $key == $dayofweek ) {
                    $class .= " doctor-schedule__today";
                    $title .= " cегодня";
                }

                $format = "<span title='%s' class='%s'>%s <br/> %s</span>";

                $weekHtmlOutput .= sprintf($format, $title, $class, $weekDays[$key]['D_DAY'], $weekDays[$key]['WEEK_DAY']);

            }
            $weekHtmlOutput .= "</div>";

            $activeWorkTimeData[$activeWorkTimeKey]['OUTPUT_HTML_FOR_WORK_DAYS'] =  $weekHtmlOutput;
        }

        return $activeWorkTimeData;
    }

    /**
     * метод находит данные пользователей
     *
     * @param array $userIds
     * @return array
     */
    private function getUsersData() {
        $activeWorkTimeData = $this->getDoctorsWithWorksDays();
        $usersIds = array_keys( $activeWorkTimeData );

        $dbUsers = \Bitrix\Main\UserTable::getList([
            'select' => ['ID','NAME','EMAIL'],
            'filter' => ['ID' => $usersIds]
        ]);

        while ($user = $dbUsers -> Fetch() ) {
            $activeWorkTimeData[$user['ID']]['USER_DATA'] = $user;
        }

        return $activeWorkTimeData;
    }

    /**
     * Метод находит дни месяца 6 дней от текущего дня
     *
     * @return array
     */
    private function getSixDaysFromCurrentDay() {
        $currentDateDayofweek = date('w', strtotime(date('Y-m-d')) );
        $dayOfMonth = date('d',strtotime(date('Y-m-d')) );

        $dateArray[] = [
            "WEEK_DAY" => $currentDateDayofweek,
            "D_DAY" => $dayOfMonth
        ];
        for( $i=1; $i<7; $i++ ){
            $dayofweek = date('w', strtotime(date('Y-m-d').'+'.$i.'day'));
            if( $dayofweek == 0 ) {
                $dayofweek = 7;
            }
            $dayOfMonth = date('d',strtotime(date('Y-m-d').'+'.$i.'day'));
            $dateArray[] = [
                "WEEK_DAY" => $dayofweek,
                "D_DAY" => $dayOfMonth
            ];
        };

        return $dateArray;
    }

    public function executeComponent()
    {
        $this->arResult['DOCTORS_SCHEDULES'] = $this->getUsersData();
        $this->includeComponentTemplate();
    }
}