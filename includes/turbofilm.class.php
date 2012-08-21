<?
	class TurboFilm
	{
		static public $config = array();

		/**
		 * Проверка доступности авторизации и авторизация при необходимости.
		 * Метод полурекурсивный.
		 *
		 * @static
		 * @param bool $reload
		 * @return bool
		 */
		static public function checkLogin( $reload = FALSE )
		{
			$res = self::_curl('http://turbofilm.tv/');

			if( !empty( $res ) && preg_match('~(Только верующий сможет пройти)~usi', $res ) )
			{
				self::_curl('http://turbofilm.tv/Signin', array( 'login' => self::$config['login'], 'passwd' => self::$config['password'], 'remember' => 'on' ) );

				if( !$reload )
				{
					return self::_checkLogin( TRUE );
				}

				return FALSE;
			}

			return TRUE;
		}

		static public function getNewSeries()
		{
			l('Запрашиваем список новых серий', 2 );

			$res = self::_curl( 'http://turbofilm.tv/My/Series' );

			if( empty( $res ) ){ l('null body, '. __LINE__, 1 ); return FALSE; }

			$html = str_get_html( $res );

			$res = $html->find('.myseriesbox');

			if( empty( $res ) )
			{
				l('Новых серий не обнаруженно' . __LINE__, 2 );
				return FALSE;
			}

			$task = Etask::getInstance();

			foreach( $res as $item )
			{

				foreach( $item->find('a') as $url )
				{
					if( $task->ok() && preg_match('~/Watch/~u', $url->href ) )
					{
						$ep = new Episode( 'http://turbofilm.tv' . $url->href );

						$url = $ep->url_cdn;

						if( !empty( $url ) )
						{
							$task->addEpisode( $ep );

							l( 'Серия ' . $ep->name .' добавленна в очередь' );
						}
					}
				}
			}

			return TRUE;
		}


		static public function getAllSeries()
		{
			l('Начинаем процесс скачивания всех серий');

			self::_getMySerials();
		}


		static private function _getMySerials()
		{
			$res = self::_curl('http://turbofilm.tv/My');

			if( empty( $res ) ){ l('Empty body / ' . __LINE__ ); return FALSE; }

			$html = str_get_html( $res );

			$serials = $html->find('.myseriesc', 0);

			foreach( $serials->find('a') as $ser )
			{
				$seasons = array();
				$seasons = self::_getSeasonsOfSerial( 'http://turbofilm.tv' . $ser->href );

				if( empty( $seasons ) ){ l('Не нашли сезонов / ' . $ser->href ); return FALSE; }

				$seasons = array_reverse( $seasons, TRUE );

				$r = self::_getEpisodesOfSeason( $seasons );

				if( $r == 102 )
				{
					break;
				}
			}
		}


		static private function _getEpisodesOfSeason( $urls )
		{
			$task = Etask::getInstance();

			foreach( $urls as $url )
			{
				l('Запрашиваем список серий сериала / ' . $url );

				$res = self::_curl( 'http://turbofilm.tv' . $url );

				if( empty( $res ) ){ l('Empty body / '. $url .' / ' . __LINE__ ); return FALSE; }

				$html = str_get_html( $res );

				$res = $html->find('.sserieslistbox', 0)->find('a');

				$series = array();

				foreach( $res as $ser )
				{
					if( $task->ok() )
					{
						$ep = new Episode('http://turbofilm.tv' . $ser->href );

						TurboFilm::_curl('http://turbofilm.tv/services/epwatch', array('eid' => $ep->eid, 'watch' => 0) );
					}
					else
					{
						return 102;
					}
				}
			}
		}

		static private function _getSeasonsOfSerial( $url )
		{
			l('Получаю спиок сезонов / ' . $url );

			$res = self::_curl( $url );

			if( empty( $res ) ){ l('Empty body / ' . __LINE__ ); return FALSE; }

			$html = str_get_html( $res );

			$urls = array();

			foreach( $html->find('.seasonnum', 0 )->find('a') as $a )
			{
				$urls[] = $a->href ;
			}

			return $urls ;
		}


		static public function _curl( $url, $post = null )
		{

			sleep( mt_rand(3,10) );

			$ch = curl_init();

	        curl_setopt($ch, CURLOPT_URL, 				$url );
	        curl_setopt($ch, CURLOPT_HEADER, 			FALSE );
	        curl_setopt($ch, CURLOPT_NOBODY, 			FALSE );
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 	TRUE );
	        curl_setopt($ch, CURLOPT_REFERER, 			'http://turbofilm.tv' );
	        curl_setopt($ch, CURLOPT_USERAGENT, 		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:8.0.1) Gecko/20100101 Firefox/8.0.1' );
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 	TRUE );
			curl_setopt($ch, CURLOPT_COOKIEJAR, 		self::$config['cookie_file'] );
			curl_setopt($ch, CURLOPT_COOKIEFILE, 		self::$config['cookie_file'] );
	        curl_setopt($ch, CURLOPT_COOKIE, 			self::_makeCookie() );

			if( !empty( $post ) )
			{
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post );
			}

	        $data = curl_exec($ch);
	        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	        curl_close($ch);

	        if( $httpCode == 200 )
	        {
				return $data ;
	        }
	        else
	        {
	            l('Not normal http respoce code / ' . $url . ' / ' . $httpCode );
	        }
		}

		/**
		 * Я че-то не знаю других способов получить куку из файлов этого формата.
		 * @return string
		 */
		static public function _makeCookie()
		{

			$data = file_get_contents( self::$config['cookie_file'] );

			preg_match_all('~IAS_ID	([a-z0-9]{40})~', $data, $found );

			if( !empty( $found[1][0] ) )
			{
				return $found[1][0];
			}
			else
			{
				l('COOKIE VALUE NON FOUND', 0 );
				// :TODO: Тут бы эксепшн кидать и выходить.
				return FALSE;
			}
		}

		/**
		 * Легкий способ найти и удалить не правильные файлы.
		 * Не правильными считаются файлы маленького размера
		 */
		static public function deleteNullFiles()
		{
			exec('find ' . escapeshellarg( TurboFilm::$config['download_dir'] ) . ' -iname "*.mp4" -size 0c -print0 | xargs rm -f {}\;', $output, $retval );
		}
	}
