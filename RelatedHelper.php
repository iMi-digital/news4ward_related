<?php

/**
 * News4ward
 * a contentelement driven news/blog-system
 *
 * @author Christoph Wiechert <wio@psitrax.de>
 * @copyright 4ward.media GbR <http://www.4wardmedia.de>
 * @package news4ward_related
 * @filesource
 * @licence LGPL
 */
namespace Psi\News4ward;

class RelatedHelper extends \Controller
{

	public function __construct()
	{
		parent::__construct();
	}

	protected $viewCreateQry = "CREATE OR REPLACE VIEW `tl_news4ward_articleWithTags` AS
  SELECT tl_news4ward_article.*, GROUP_CONCAT(tag) AS tags
  FROM tl_news4ward_article
  LEFT OUTER JOIN tl_news4ward_tag ON (tl_news4ward_tag.pid = tl_news4ward_article.id)
  GROUP BY tl_news4ward_article.id";

	/**
	 * Handle database update
	 * its a callback, executed within install-tool
	 * @param array $arrData SQL-Statements
	 * @return array SQL-Statements
	 */
	public function sqlCompileCommands($arrData)
	{
		$this->import('Database');
		$arrAllModules = scan(TL_ROOT . '/system/modules');

		// remove DROP statment for tl_news4ward_articleWithTags if news4ward_tags is installed
		if(isset($arrData['DROP']) && is_array($arrData['DROP']))
		{
			foreach($arrData['DROP'] as $k => $v)
			{
				if($v == 'DROP TABLE `tl_news4ward_articleWithTags`;' && in_array('news4ward_tags',$arrAllModules))
				{
					// prevent view from deletion
					unset($arrData['DROP'][$k]);
				}
				elseif($v == 'DROP TABLE `tl_news4ward_articleWithTags`;')
				{
					// tell contao to do a DROP VIEW instead of DROP TABLE
					unset($arrData['DROP'][$k]);
					$arrData['DROP'][] = 'DROP VIEW `tl_news4ward_articleWithTags`;';
				}
			}
			if(!count($arrData['DROP'])) unset($arrData['DROP']);
		}

		// if news4ward_tags is installed tell contao to CREATE VIEW if not already done
		if(in_array('news4ward_tags',$arrAllModules) && !$this->Database->tableExists('tl_news4ward_articleWithTags'))
		{
            $arrData['CREATE'][md5($this->viewCreateQry)] = $this->viewCreateQry;
        }

		return $arrData;
	}


	/**
	 * Repeair broken VIEW on demand
	 */
	public function fixView()
	{
		\BackendUser::getInstance(); // kid php destructor: we need to destruct BackendUser *before* Database
		$this->import('Database');
		if(!$this->Database->tableExists('tl_news4ward_articleWithTags')) return;
		try {
			$this->Database->query('SHOW INDEXES FROM `tl_news4ward_articleWithTags`');
		} catch(\Exception $e) {
			$this->Database->query($this->viewCreateQry);
		}
	}
}
