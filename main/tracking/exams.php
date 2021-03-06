<?php
/* For licensing terms, see /license.txt */
/**
 * Exams script
 * @package chamilo.tracking
 */
/**
 * Code
 */

$language_file = array('registration', 'index', 'tracking', 'exercice','survey');
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'pear/Spreadsheet_Excel_Writer/Writer.php';

$toolTable = Database::get_course_table(TABLE_TOOL_LIST);
$quizTable = Database::get_course_table(TABLE_QUIZ_TEST);

$this_section = SECTION_TRACKING;
$is_allowedToTrack = $is_courseAdmin || $is_platformAdmin || $is_courseCoach || $is_sessionAdmin;

if (!$is_allowedToTrack) {
    api_not_allowed();
}

$exportToXLS = false;
if (isset($_GET['export'])) {
    $exportToXLS = true;
}

if (api_is_platform_admin() && empty($_GET['cidReq'])) {
    $global = true;
} else {
    $global = false;
}

$courseList = array();
if ($global) {
    $temp = CourseManager::get_courses_list();
    foreach ($temp as $tempCourse) {
        $courseInfo = api_get_course_info($tempCourse['code']);
        $courseList[] = $courseInfo;
    }
} else {
    $courseList = array(api_get_course_info());
}

$sessionId = api_get_session_id();

if (empty($sessionId)) {
    $sessionCondition = ' AND session_id = 0';
} else {
    $sessionCondition = api_get_session_condition($sessionId, true, true);
}

$form = new FormValidator('search_simple', 'POST', '', '', null, false);
$form->addElement('text', 'score', get_lang('Percentage'));
if ($global) {
    $form->addElement('hidden', 'view', 'admin');
} else {
    // Get exam lists
    $courseId = api_get_course_int_id();

    $sql = "SELECT quiz.title, id FROM $quizTable AS quiz
            WHERE
                c_id = $courseId AND
                active='1'
                $sessionCondition
            ORDER BY quiz.title ASC";
    $result = Database::query($sql);

    $exerciseList = array(get_lang('All'));
    while ($row = Database::fetch_array($result)) {
        $exerciseList[$row['id']] = $row['title'];
    }

    $form->addElement('select', 'exercise_id', get_lang('Exercise'), $exerciseList);
}

$form->addElement('style_submit_button', 'submit', get_lang('Filter'), 'class="search"');

$filter_score = isset($_REQUEST['score']) ? intval($_REQUEST['score']) : 70;
$exerciseId = isset($_REQUEST['exercise_id']) ? intval($_REQUEST['exercise_id']) : 0;

$form->setDefaults(array('score' => $filter_score));

if (!$exportToXLS) {
    Display :: display_header(get_lang('Reporting'));
    echo '<div class="actions">';
    if ($global) {

        echo '<a href="'.api_get_path(WEB_CODE_PATH).'auth/my_progress.php">'.
        Display::return_icon('stats.png', get_lang('MyStats'), '', ICON_SIZE_MEDIUM);
        echo '</a>';

        echo '<span style="float:right">';

        echo '<a href="'.api_get_self().'?export=1&score='.$filter_score.'&exercise_id='.$exerciseId.'&'.api_get_cidreq().'">'.
            Display::return_icon('export_excel.png',get_lang('ExportAsXLS'),'',ICON_SIZE_MEDIUM).'</a>';
        echo '<a href="javascript: void(0);" onclick="javascript: window.print()">'.
            Display::return_icon('printer.png',get_lang('Print'),'',ICON_SIZE_MEDIUM).'</a>';
        echo '</span>';

        $menuItems[] = Display::url(
            Display::return_icon('teacher.png', get_lang('TeacherInterface'), array(), 32),
            api_get_path(WEB_CODE_PATH).'mySpace/?view=teacher'
        );
        if (api_is_platform_admin()) {
            $menuItems[] = Display::url(
                Display::return_icon('star.png', get_lang('AdminInterface'), array(), 32),
                api_get_path(WEB_CODE_PATH).'mySpace/index.php?view=admin'
            );
        } else {
            $menuItems[] = Display::url(
                Display::return_icon('star.png', get_lang('CoachInterface'), array(), 32),
                api_get_path(WEB_CODE_PATH).'mySpace/index.php?view=coach'
            );
        }
        $menuItems[] = Display::return_icon('quiz_na.png', get_lang('ExamTracking'), array(), 32);

        $nb_menu_items = count($menuItems);
        if ($nb_menu_items > 1) {
            foreach ($menuItems as $key=> $item) {
                echo $item;
            }
        }
    } else {
        echo Display::url(
            Display::return_icon('user.png', get_lang('StudentsTracking'), array(), 32),
            'courseLog.php?'.api_get_cidreq().'&amp;studentlist=true'
        );
        echo Display::url(
            Display::return_icon('course.png', get_lang('CourseTracking'), array(), 32),
            'courseLog.php?'.api_get_cidreq().'&amp;studentlist=false'
        );
        echo Display::url(
            Display::return_icon('tools.png', get_lang('ResourcesTracking'), array(), 32),
            'courseLog.php?'.api_get_cidreq().'&amp;studentlist=resouces'
        );
        echo Display::url(
            Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), array(), 32),
            api_get_self().'?'.api_get_cidreq().'&amp;export=1&amp;score='.$filter_score.'&amp;exercise_id='.$exerciseId
        );

    }
    echo '</div>';

    $form->display();
    echo '<h3>'.sprintf(get_lang('FilteringWithScoreX'), $filter_score).'%</h3>';
}

$html = null;
if ($global) {
    $html .= '<table  class="data_table">';
    $html .= '<tr><th>'.get_lang('Courses').'</th>';
    $html .= '<th>'.get_lang('Quiz').'</th>';
    $html .= '<th>'.get_lang('ExamTaken').'</th>';
    $html .= '<th>'.get_lang('ExamNotTaken').'</th>';
    $html .= '<th>'.sprintf(get_lang('ExamPassX'), $filter_score).'%</th>';
    $html .= '<th>'.get_lang('ExamFail').'</th>';
    $html .= '<th>'.get_lang('TotalStudents').'</th>';
    $html .= '</tr>';
} else {
    $html .= '<table class="data_table">';
    $html .= '<tr><th>'.get_lang('Quiz').'</th>';
    $html .= '<th>'.get_lang('User').'</th>';
    //$html .= '<th>'.sprintf(get_lang('ExamPassX'), $filter_score).'</th>';
    $html .= '<th>'.get_lang('Percentage').' %</th>';
    $html .= '<th>'.get_lang('Status').'</th>';
    $html .= '<th>'.get_lang('Attempts').'</th>';
    $html .= '</tr>';
}

$export_array_global = $export_array = array();
$s_css_class = null;

if (!empty($courseList) && is_array($courseList)) {
    foreach ($courseList as $courseInfo) {
        $sessionList = SessionManager::get_session_by_course($courseInfo['code']);

        $newSessionList = array();
        if (!empty($sessionList)) {
            foreach ($sessionList as $session) {
                $newSessionList[$session['id']] = $session['name'];
            }
        }

        $courseId = $courseInfo['real_id'];

        if ($global) {
            $sql = "SELECT count(id) as count
                    FROM $quizTable AS quiz
                    WHERE active='1' AND c_id = $courseId AND session_id = 0";
            $result = Database::query($sql);
            $countExercises = Database::store_result($result);
            $exerciseCount = $countExercises[0]['count'];

            $sql = "SELECT count(id) as count
                    FROM $quizTable AS quiz
                    WHERE active='1' AND c_id = $courseId AND session_id <> 0";
            $result = Database::query($sql);
            $countExercises = Database::store_result($result);
            $exerciseSessionCount = $countExercises[0]['count'];

            $exerciseCount =  $exerciseCount + $exerciseCount *count($newSessionList) + $exerciseSessionCount;

            // Add course and session list.
            if ($exerciseCount == 0) {
                $exerciseCount = 2;
            }
            $html .= "<tr>
                        <td rowspan=$exerciseCount>";
            $html .= $courseInfo['title'];
            $html .= "</td>";
        }

        $sql = "SELECT visibility FROM $toolTable
                WHERE c_id = $courseId AND name = 'quiz'";
        $result = Database::query($sql);

        // If main tool is visible.
        if (Database::result($result, 0 ,'visibility') == 1) {
            // Getting the exam list.
            if ($global) {
                $sql = "SELECT quiz.title, id, session_id
                    FROM $quizTable AS quiz
                    WHERE c_id = $courseId AND active='1'
                    ORDER BY session_id, quiz.title ASC";
            } else {
                //$sessionCondition = api_get_session_condition($sessionId, true, false);
                if (!empty($exerciseId)) {
                    $sql = "SELECT quiz.title, id, session_id
                            FROM $quizTable AS quiz
                            WHERE
                                c_id = $courseId AND
                                active = '1' AND
                                id = $exerciseId
                                $sessionCondition

                            ORDER BY session_id, quiz.title ASC";
                } else {

                    $sql = "SELECT quiz.title, id, session_id
                            FROM $quizTable AS quiz
                            WHERE
                                c_id = $courseId AND
                                active='1'
                                $sessionCondition
                            ORDER BY session_id, quiz.title ASC";
                }
            }

            $resultExercises = Database::query($sql);

            if (Database::num_rows($resultExercises) > 0) {
                $export_array_global = array();

                while ($exercise = Database::fetch_array($resultExercises)) {
                    $exerciseSessionId = $exercise['session_id'];

                    if (empty($exerciseSessionId)) {

                        if ($global) {
                            // If the exercise was created in the base course.
                            // Load all sessions.
                            foreach ($newSessionList as $currentSessionId => $sessionName) {
                                $result = processStudentList(
                                    $filter_score,
                                    $global,
                                    $exercise,
                                    $courseInfo,
                                    $currentSessionId,
                                    $newSessionList
                                );

                                $html .= $result['html'];
                                $export_array_global = array_merge($export_array_global, $result['export_array_global']);
                            }

                            // Load base course.
                            $result = processStudentList(
                                $filter_score,
                                $global,
                                $exercise,
                                $courseInfo,
                                0,
                                $newSessionList
                            );
                            $html .= $result['html'];
                            $export_array_global = array_merge($export_array_global, $result['export_array_global']);
                        } else {

                            if (empty($sessionId)) {
                                // Load base course.
                                $result = processStudentList(
                                    $filter_score,
                                    $global,
                                    $exercise,
                                    $courseInfo,
                                    0,
                                    $newSessionList
                                );

                                $html .= $result['html'];
                                $export_array_global = array_merge(
                                    $export_array_global,
                                    $result['export_array_global']
                                );
                            } else {

                                $result = processStudentList(
                                    $filter_score,
                                    $global,
                                    $exercise,
                                    $courseInfo,
                                    $sessionId,
                                    $newSessionList
                                );

                                $html .= $result['html'];
                                $export_array_global = array_merge(
                                    $export_array_global,
                                    $result['export_array_global']
                                );
                            }
                        }

                    } else {
                        // If the exercise only exists in this session.

                        $result = processStudentList(
                            $filter_score,
                            $global,
                            $exercise,
                            $courseInfo,
                            $exerciseSessionId,
                            $newSessionList
                        );

                        $html .= $result['html'];
                        $export_array_global = array_merge($export_array_global, $result['export_array_global']);
                    }
                }
            } else {
                $html .= "<tr>
                            <td colspan='6'>
                                ".get_lang('NoExercise')."
                            </td>
                        </tr>
                     ";
            }
        } else {
            $html .= "<tr>
                        <td colspan='6'>
                            ".get_lang('NoExercise')."
                        </td>
                    </tr>
                 ";
        }
    }
}

$html .= '</table>';

if (!$exportToXLS) {
    echo $html;
}

$filename = 'exam-reporting-'.date('Y-m-d-h:i:s').'.xls';
if ($exportToXLS) {
    if ($global) {
        export_complete_report_xls($filename, $export_array_global);
    } else {
        export_complete_report_xls($filename, $export_array);
    }
    exit;
}
/**
 * @param $a
 * @param $b
 * @return int
 */
function sort_user($a, $b) {
    if (is_numeric($a['score']) && is_numeric($b['score'])) {
        if ($a['score'] < $b['score']) {
            return 1;
        }
        return 0;
    }
    return 1;
}

/**
 * @param string $filename
 * @param array $array
 */
function export_complete_report_xls($filename, $array)
{
    global $charset, $global, $filter_score;
    $workbook = new Spreadsheet_Excel_Writer();
    $workbook ->setTempDir(api_get_path(SYS_ARCHIVE_PATH));
    $workbook->send($filename);
    $workbook->setVersion(8); // BIFF8
    $worksheet =& $workbook->addWorksheet('Report');
    //$worksheet->setInputEncoding(api_get_system_encoding());
    $worksheet->setInputEncoding($charset);

    $line = 0;
    $column = 0; //skip the first column (row titles)

    if ($global) {
        $worksheet->write($line, $column, get_lang('Courses'));
        $column++;
        $worksheet->write($line, $column, get_lang('Exercises'));
        $column++;
        $worksheet->write($line, $column, get_lang('ExamTaken'));
        $column++;
        $worksheet->write($line, $column, get_lang('ExamNotTaken'));
        $column++;
        $worksheet->write($line, $column, sprintf(get_lang('ExamPassX'), $filter_score) . '%');
        $column++;
        $worksheet->write($line, $column, get_lang('ExamFail'));
        $column++;
        $worksheet->write($line, $column, get_lang('TotalStudents'));
        $column++;

        $line++;
        foreach ($array as $row) {
            $column = 0;
            foreach ($row as $item) {
                $worksheet->write($line,$column,html_entity_decode(strip_tags($item)));
                $column++;
            }
            $line++;
        }
        $line++;
    } else {
        $worksheet->write($line,$column,get_lang('Exercises'));
        $column++;
        $worksheet->write($line,$column,get_lang('User'));
        $column++;
        $worksheet->write($line,$column,get_lang('Percentage'));
        $column++;
        $worksheet->write($line,$column,get_lang('Status'));
        $column++;
        $worksheet->write($line,$column,get_lang('Attempts'));
        $column++;
        $line++;
        foreach ($array as $row) {
            $column = 0;
            $worksheet->write($line,$column,html_entity_decode(strip_tags($row['exercise'])));
            $column++;
            foreach ($row['users'] as $key=>$user) {
                $column = 1;
                $worksheet->write($line,$column,html_entity_decode(strip_tags($user)));
                $column++;
                foreach ($row['results'][$key] as $result_item) {
                    $worksheet->write($line,$column,html_entity_decode(strip_tags($result_item)));
                    $column++;
                }
                $line++;
            }
        }
        $line++;
    }
    $workbook->close();
    exit;
}

function processStudentList($filter_score, $global, $exercise, $courseInfo, $sessionId, $newSessionList)
{
    $exerciseStatsTable = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);

    if (empty($sessionId)) {
        $students = CourseManager::get_student_list_from_course_code(
            $courseInfo['code'],
            false
        );
    } else {
        $students = CourseManager::get_student_list_from_course_code(
            $courseInfo['code'],
            true,
            $sessionId
        );
    }

    $html = null;

    $totalStudents = count($students);

    if (!$global) {
        $html .= "<tr>";
    }

    if (!$global) {
        $html .= '<td rowspan="'.$totalStudents.'">';
    } else {
        $html .= '<td>';
    }

    $html .= $exercise['title'];

    if ($global && !empty($sessionId)) {
        $sessionName = isset($newSessionList[$sessionId]) ? $newSessionList[$sessionId] : null;
        $html .= Display::return_icon('star.png', get_lang('Session')).' ('.$sessionName.')';
    }

    $html .= '</td>';

    $globalRow = array(
        $courseInfo['title'],
        $exercise['title']
    );

    $total_with_parameter_score = 0;
    $taken = 0;
    $export_array_global = array();
    $studentResult = array();

    foreach ($students as $student) {
        $studentId = isset($student['user_id']) ? $student['user_id'] : $student['id_user'];
        $sql = "SELECT COUNT(ex.exe_id) as count
                FROM $exerciseStatsTable AS ex
                WHERE
                    ex.exe_cours_id = '".$courseInfo['code']."' AND
                    ex.exe_exo_id = ".$exercise['id']." AND
                    exe_user_id='".$studentId."' AND
                    session_id = $sessionId
                ";
        $result = Database::query($sql);
        $attempts = Database::fetch_array($result);

        $sql = "SELECT exe_id, exe_result, exe_weighting
                FROM $exerciseStatsTable
                WHERE
                    exe_user_id = ".$studentId." AND
                    exe_cours_id = '".$courseInfo['code']."' AND
                    exe_exo_id = ".$exercise['id']." AND
                    session_id = $sessionId
                ORDER BY exe_result DESC
                LIMIT 1";
        $result = Database::query($sql);
        $score = 0;
        $weighting = 0;
        while ($scoreInfo = Database::fetch_array($result)) {
            $score = $score + $scoreInfo['exe_result'];
            $weighting = $weighting + $scoreInfo['exe_weighting'];
        }

        $percentageScore = 0;

        if ($weighting != 0) {
            $percentageScore = round(($score*100)/$weighting);
        }

        if ($attempts['count'] > 0 ) {
            $taken++;
        }

        if ($percentageScore >= $filter_score) {
            $total_with_parameter_score++;
        }

        $tempArray = array();

        if (!$global) {
            $userInfo = api_get_user_info($studentId);

            // User
            $userRow = '<td>';
            $userRow .= $userInfo['complete_name'];
            $userRow .= '</td>';

            // Best result

            if (!empty($attempts['count'])) {
                $userRow .= '<td>';
                $userRow .= $percentageScore;
                $tempArray[] = $percentageScore;
                $userRow .= '</td>';

                if ($percentageScore >= $filter_score) {
                    $userRow .= '<td style="background-color:#DFFFA8">';
                    $userRow .= get_lang('PassExam').'</td>';
                    $tempArray[] = get_lang('PassExam');
                } else {
                    $userRow .= '<td style="background-color:#FC9A9E"  >';
                    $userRow .= get_lang('ExamFail').'</td>';
                    $tempArray[] = get_lang('ExamFail');
                }

                $userRow .= '<td>';
                $userRow .= $attempts['count'];
                $tempArray[] = $attempts['count'];
                $userRow .= '</td>';
            } else {
                $score = '-';
                $userRow .= '<td>';
                $userRow .=  '-';
                $tempArray[] = '-';
                $userRow .= '</td>';

                $userRow .= '<td style="background-color:#FCE89A">';
                $userRow .= get_lang('NoAttempt');
                $tempArray[] = get_lang('NoAttempt');
                $userRow .= '</td>';
                $userRow .= '<td>';
                $userRow .= 0;
                $tempArray[] = 0;
                $userRow .= '</td>';
            }
            $userRow .= '</tr>';

            $studentResult[$studentId] = array(
                'html' => $userRow,
                'score' => $score,
                'array' => $tempArray,
                'user' => $userInfo['complete_name']
            );
        }
    }

    $row_not_global['exercise'] = $exercise['title'];

    if (!$global) {
        if (!empty($studentResult)) {
            $studentResultEmpty = $studentResultContent = array();
            foreach ($studentResult as $row) {
                if ($row['score'] == '-') {
                    $studentResultEmpty[] = $row;
                } else {
                    $studentResultContent[] = $row;
                }
            }

            // Sort only users with content
            usort($studentResultContent, 'sort_user');
            $studentResult = array_merge($studentResultContent, $studentResultEmpty);

            foreach ($studentResult as $row) {
                $html .= $row['html'];
                $row_not_global['results'][] = $row['array'];
                $row_not_global['users'][] = $row['user'];
            }
            $export_array[] = $row_not_global;
        }
    }


    if ($global) {
        // Exam taken
        $html .= '<td>';
        $html .= $taken;
        $globalRow[]= $taken;
        $html .= '</td>';

        // Exam NOT taken
        $html .= '<td>';
        $html .= $not_taken = $totalStudents - $taken;
        $globalRow[]= $not_taken;
        $html .= '</td>';

        // Exam pass
        if (!empty($total_with_parameter_score)) {
            $html .= '<td style="background-color:#DFFFA8" >';
        } else {
            $html .= '<td style="background-color:#FCE89A"  >';
        }

        $html .= $total_with_parameter_score;
        $globalRow[]= $total_with_parameter_score;
        $html .= '</td>';

        // Exam fail
        $html .= '<td>';

        $html .= $fail = $taken - $total_with_parameter_score;
        $globalRow[]= $fail;
        $html .= '</td>';

        $html .= '<td>';
        $html .= $totalStudents;
        $globalRow[]= $totalStudents;

        $html .= '</td>';

        $html .= '</tr>';
        $export_array_global[] = $globalRow;
    }

    return array(
        'html' => $html,
        'export_array_global' => $export_array_global,
        'total_students' => $totalStudents
    );
}
Display :: display_footer();
