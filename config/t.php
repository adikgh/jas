<?php

	// Аудармалар. Қазір тек қазақша. Орыс тілін қосу: әр жолға 'ru' => '...' жазу.
	class t {
		public static $lang = 'kz';
		public static $langs = ['kz'];   // кейін: ['kz', 'ru']

		public static $w = [
			// рөлдер
			'owner' => ['kz' => 'Иесі'],
			'admin' => ['kz' => 'Әкімші'],
			'coach' => ['kz' => 'Бапкер'],
			'parent' => ['kz' => 'Ата-ана'],
			'participant' => ['kz' => 'Қатысушы'],

			// бағыттар
			'kids' => ['kz' => 'ZHASS KIDS'],
			'adult' => ['kz' => 'ZHASS ADULT'],
			'first_team' => ['kz' => 'FIRST TEAM'],

			// позиция
			'gk' => ['kz' => 'Қақпашы'],
			'df' => ['kz' => 'Қорғаушы'],
			'mf' => ['kz' => 'Жартылай қорғаушы'],
			'fw' => ['kz' => 'Шабуылшы'],

			// аяқ
			'right' => ['kz' => 'Оң аяқ'],
			'left' => ['kz' => 'Сол аяқ'],
			'both' => ['kz' => 'Екі аяқ'],

			// қатысушы мәртебесі
			'active' => ['kz' => 'Белсенді'],
			'paused' => ['kz' => 'Үзіліс'],
			'left_st' => ['kz' => 'Кетті'],

			// қатысу
			'present' => ['kz' => 'Келді'],
			'absent' => ['kz' => 'Келмеді'],
			'excused' => ['kz' => 'Себепті'],
			'frozen' => ['kz' => 'Заморозка'],

			// абонемент
			'sub_pending' => ['kz' => 'Төлем күтілуде'],
			'sub_active' => ['kz' => 'Белсенді'],
			'sub_frozen' => ['kz' => 'Заморозкада'],
			'sub_expired' => ['kz' => 'Мерзімі өтті'],
			'sub_cancelled' => ['kz' => 'Тоқтатылды'],

			// төлем әдістері
			'kaspi' => ['kz' => 'Kaspi'],
			'kaspi_red' => ['kz' => 'Kaspi RED'],
			'bank' => ['kz' => 'Банктік аударым'],
			'cash' => ['kz' => 'Қолма-қол'],
			'pay_pending' => ['kz' => 'Расталмаған'],
			'pay_confirmed' => ['kz' => 'Расталған'],
			'pay_rejected' => ['kz' => 'Қабылданбады'],

			// лид
			'lead_new' => ['kz' => 'Жаңа'],
		];

		public function __construct() {}

		public static function w($key) {
			if (!isset(self::$w[$key])) return $key;
			$r = self::$w[$key];
			return isset($r[self::$lang]) ? $r[self::$lang] : $r['kz'];
		}
	}
