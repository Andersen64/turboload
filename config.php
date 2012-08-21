<?php

	/*
		login       - ��� �����
		password    - ��� ������
		watch       - �������� ����� ��� ������������� ����� ����������
		language    - ���� �� ������� ��������� �����
		tasks       - ������������ ���-�� ����� ����������� �� ���
		email       - email ��� �����������
		download_dir- ���� � ����� ��� ��������

		cookie_file - ���� � cookie �����, ����� �� ��������
		tools       - ����� � ������ � ����������
		only_hq     - ��������� ������ � ������� ��������
				�� ��������� ������ ���������� ��������� ��������.
				���� ���������� � TRUE, �� ����� �� � HQ ����������� �� �����
	 */

	TurboFilm::$config = array(
		'login'			=> 'login',
		'password'		=> 'password',
		'watch'         => 1,
		'language'      => 'ru', // ru | en
		'tasks'			=> 5,
		'email'         => array('mail@me.com'),
		'download_dir' 	=> realpath( __DIR__ ) . '/downloads',

		'cookie_file'	=> realpath(__DIR__) . '/cookie.txt',
		'tools'         => array(
			'wget'      => '/usr/bin/wget'
		),
		'only_hq'       => FALSE,

		'log_file' 		=> realpath( __DIR__ ) . '/downloads.log',
	);

