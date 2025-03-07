<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'ProgramFunctions/Template.fnc.php';
require_once 'ProgramFunctions/Substitutions.fnc.php';

if ( User( 'PROFILE' ) === 'teacher' )
{
	$_ROSARIO['allow_edit'] = true;
}

if ( $_REQUEST['modfunc'] === 'save'
	&& AllowEdit() )
{
	if ( empty( $_REQUEST['st_arr'] ) )
	{
		BackPrompt( _( 'You must choose at least one student.' ) );
	}

	$_REQUEST['mailing_labels'] = issetVal( $_REQUEST['mailing_labels'], '' );

	$_REQUEST['hide_headers'] = issetVal( $_REQUEST['hide_headers'], '' );

	$st_list = implode( ',', array_map( 'intval', $_REQUEST['st_arr'] ) );

	$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

	if ( $_REQUEST['mailing_labels'] == 'Y' )
	{
		Widgets( 'mailing_labels' );
	}

	$extra['SELECT'] = issetVal( $extra['SELECT'], '' );

	// SELECT s.* Custom Fields for Substitutions.
	$extra['SELECT'] .= ",s.*";

	// Close #348 Add School Year & Principal to Substitutions
	$extra['SELECT'] .= ",(SELECT sch.PRINCIPAL FROM schools sch
		WHERE ssm.SCHOOL_ID=sch.ID
		AND sch.SYEAR='" . UserSyear() . "') AS SCHOOL_PRINCIPAL";

	if ( User( 'PROFILE' ) === 'admin' )
	{
		if ( isset( $_REQUEST['w_course_period_id_which'] )
			&& $_REQUEST['w_course_period_id_which'] == 'course_period'
			&& $_REQUEST['w_course_period_id'] )
		{
			$extra['SELECT'] .= ",(SELECT " . DisplayNameSQL( 'st' ) . "
			FROM staff st,course_periods cp
			WHERE st.STAFF_ID=cp.TEACHER_ID
			AND cp.COURSE_PERIOD_ID='" . (int) $_REQUEST['w_course_period_id'] . "') AS TEACHER";

			$extra['SELECT'] .= ",(SELECT cp.ROOM FROM course_periods cp WHERE cp.COURSE_PERIOD_ID='" . (int) $_REQUEST['w_course_period_id'] . "') AS ROOM";
		}
		else
		{
			//FJ multiple school periods for a course period
			//$extra['SELECT'] .= ",(SELECT " . DisplayNameSQL( 'st' ) . "  FROM staff st,course_periods cp,school_periods p,schedule ss WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.PERIOD_id=p.PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER IS NULL,p.SORT_ORDER LIMIT 1) AS TEACHER";
			// SQL Replace AND p.ATTENDANCE='Y' with AND cp.DOES_ATTENDANCE IS NOT NULL.
			$extra['SELECT'] .= ",(SELECT " . DisplayNameSQL( 'st' ) . "
			FROM staff st,course_periods cp,school_periods p,schedule ss,course_period_school_periods cpsp
			WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
			AND st.STAFF_ID=cp.TEACHER_ID
			AND cpsp.PERIOD_id=p.PERIOD_ID
			AND cp.DOES_ATTENDANCE IS NOT NULL
			AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
			AND ss.STUDENT_ID=s.STUDENT_ID
			AND ss.SYEAR='" . UserSyear() . "'
			AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', GetCurrentMP( 'QTR', DBDate(), false ) ) . ")
			AND (ss.START_DATE<='" . DBDate() . "'
				AND (ss.END_DATE>='" . DBDate() . "' OR ss.END_DATE IS NULL))
			ORDER BY p.SORT_ORDER IS NULL,p.SORT_ORDER LIMIT 1) AS TEACHER";

			//$extra['SELECT'] .= ",(SELECT cp.ROOM FROM course_periods cp,school_periods p,schedule ss WHERE cp.PERIOD_id=p.PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER IS NULL,p.SORT_ORDER LIMIT 1) AS ROOM";
			// SQL Replace AND p.ATTENDANCE='Y' with AND cp.DOES_ATTENDANCE IS NOT NULL.
			$extra['SELECT'] .= ",(SELECT cp.ROOM
			FROM course_periods cp,school_periods p,schedule ss,course_period_school_periods cpsp
			WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
			AND cpsp.PERIOD_id=p.PERIOD_ID
			AND cp.DOES_ATTENDANCE IS NOT NULL
			AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
			AND ss.STUDENT_ID=s.STUDENT_ID
			AND ss.SYEAR='" . UserSyear() . "'
			AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', GetCurrentMP( 'QTR', DBDate(), false ) ) . ")
			AND (ss.START_DATE<='" . DBDate() . "' AND (ss.END_DATE>='" . DBDate() . "' OR ss.END_DATE IS NULL))
			ORDER BY p.SORT_ORDER IS NULL,p.SORT_ORDER LIMIT 1) AS ROOM";
		}
	}
	else
	{
		$extra['SELECT'] .= ",(SELECT " . DisplayNameSQL( 'st' ) . "
		FROM staff st,course_periods cp
		WHERE st.STAFF_ID=cp.TEACHER_ID
		AND cp.COURSE_PERIOD_ID='" . UserCoursePeriod() . "') AS TEACHER";

		$extra['SELECT'] .= ",(SELECT cp.ROOM FROM course_periods cp WHERE cp.COURSE_PERIOD_ID='" . UserCoursePeriod() . "') AS ROOM";
	}

	if ( empty( $_REQUEST['_search_all_schools'] ) )
	{
		// School Title.
		$extra['SELECT'] .= ",(SELECT sch.TITLE FROM schools sch
			WHERE ssm.SCHOOL_ID=sch.ID
			AND sch.SYEAR='" . UserSyear() . "') AS SCHOOL_TITLE";
	}

	$RET = GetStuList( $extra );

	if ( empty( $RET ) )
	{
		BackPrompt( _( 'No Students were found.' ) );
	}

	SaveTemplate( DBEscapeString( SanitizeHTML( $_POST['letter_text'] ) ) );

	$letter_text_template = GetTemplate();

	$handle = PDFStart();

	foreach ( (array) $RET as $student )
	{
		unset( $_ROSARIO['DrawHeader'] );

		if ( ! $_REQUEST['hide_headers'] )
		{
			DrawHeader( '&nbsp;' );
			DrawHeader( $student['FULL_NAME'], $student['STUDENT_ID'] );
			DrawHeader( $student['GRADE_ID'], $student['SCHOOL_TITLE'] );
			DrawHeader( ProperDate( DBDate() ) );
		}
		elseif ( $_REQUEST['mailing_labels'] === 'Y' )
		{
			echo '<br /><br /><br /><br /><br /><br /><br /><br /><br />';
		}

		if ( $_REQUEST['mailing_labels'] === 'Y' )
		{
			// @since 11.6 Add Mailing Label position
			echo MailingLabelPositioned( $student['MAILING_LABEL'] );
		}

		$substitutions = [
			'__FULL_NAME__' => $student['FULL_NAME'],
			'__LAST_NAME__' => $student['LAST_NAME'],
			'__FIRST_NAME__' => $student['FIRST_NAME'],
			'__MIDDLE_NAME__' =>  $student['MIDDLE_NAME'],
			'__STUDENT_ID__' => $student['STUDENT_ID'],
			'__SCHOOL_TITLE__' => $student['SCHOOL_TITLE'],
			'__SCHOOL_YEAR__' => FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ),
			'__SCHOOL_PRINCIPAL__' => $student['SCHOOL_PRINCIPAL'],
			'__GRADE_ID__' => $student['GRADE_ID'],
			'__TEACHER__' => $student['TEACHER'],
			'__ROOM__' => $student['ROOM'],
		];

		$substitutions += SubstitutionsCustomFieldsValues( 'STUDENT', $student );

		$letter_text = SubstitutionsTextMake( $substitutions, $letter_text_template );

		echo '<br />' . $letter_text;
		echo '<div style="page-break-after: always;"></div>';
	}

	PDFStop( $handle );
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( ProgramTitle() );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&include_inactive=' .
			issetVal( $_REQUEST['include_inactive'], '' ) . '&_search_all_schools=' .
			issetVal( $_REQUEST['_search_all_schools'], '' ) . '&_ROSARIO_PDF=true' ) . '" method="POST">';

		$extra['header_right'] = SubmitButton( _( 'Print Letters for Selected Students' ) );

		Widgets( 'mailing_labels' );
		$extra['extra_header_left'] = '<table>' . $extra['search'] . '</table>';
		$extra['search'] = '';

		// FJ add TinyMCE to the textarea.
		$extra['extra_header_left'] .= '<table class="width-100p"><tr class="st"><td>' .
		TinyMCEInput(
			GetTemplate(),
			'letter_text',
			_( 'Letter Text' )
		) . '</td></tr>';

		$substitutions = [
			'__FULL_NAME__' => _( 'Display Name' ),
			'__LAST_NAME__' => _( 'Last Name' ),
			'__FIRST_NAME__' => _( 'First Name' ),
			'__MIDDLE_NAME__' =>  _( 'Middle Name' ),
			'__STUDENT_ID__' => sprintf( _( '%s ID' ), Config( 'NAME' ) ),
			'__SCHOOL_TITLE__' => _( 'School' ),
			'__SCHOOL_YEAR__' => _( 'School Year' ),
			'__SCHOOL_PRINCIPAL__' => _( 'Principal of School' ),
			'__GRADE_ID__' => _( 'Grade Level' ),
		];

		if ( User( 'PROFILE' ) === 'admin' )
		{
			$substitutions['__TEACHER__'] = _( 'Attendance Teacher' );
			$substitutions['__ROOM__'] = _( 'Attendance Room' );
		}
		else
		{
			$substitutions['__TEACHER__'] = _( 'Your Name' );
			$substitutions['__ROOM__'] = _( 'Your Room' );
		}

		$substitutions += SubstitutionsCustomFields( 'student' );

		$extra['extra_header_left'] .= '<tr class="st"><td>' .
			SubstitutionsInput( $substitutions ) .
		'</td></tr>';

		// @since 11.5 Add Hide Headers option
		$extra['extra_header_left'] .= '<tr class="st"><td>' . CheckboxInput(
			issetVal( $_REQUEST['hide_headers'], '' ),
			'hide_headers',
			_( 'Hide Headers' ),
			'',
			true
		) . '</td></tr></table>';

		/**
		 * Print Letters header
		 * Add your headers to
		 * @global $extra['extra_header_left']
		 *
		 * @since 11.4.1
		 */
		do_action( 'Students/Letters.php|header' );
	}

	$extra['SELECT'] = issetVal( $extra['SELECT'], '' );

	$extra['SELECT'] .= ",s.STUDENT_ID AS CHECKBOX";
	$extra['link'] = [ 'FULL_NAME' => false ];
	$extra['functions'] = [ 'CHECKBOX' => 'MakeChooseCheckbox' ];
	$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( 'Y_required', '', 'st_arr' ) ];
	$extra['options']['search'] = false;
	$extra['new'] = true;

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' .
		SubmitButton( _( 'Print Letters for Selected Students' ) ) . '</div></form>';
	}
}
