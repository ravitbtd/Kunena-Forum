<?php
/**
 * Kunena Component
 *
 * @package       Kunena.Framework
 * @subpackage    Forum.Category
 *
 * @copyright     Copyright (C) 2008 - 2020 Kunena Team. All rights reserved.
 * @license       https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link          https://www.kunena.org
 **/

namespace Kunena;

defined('_JEXEC') or die();

use Exception;
use InvalidArgumentException;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use function defined;

/**
 * Class KunenaForumCategory
 *
 * @property int    $id
 * @property int    $parent_id
 * @property string $name
 * @property string $alias
 * @property int    $icon_id
 * @property int    $locked
 * @property string $accesstype
 * @property int    $access
 * @property int    $pub_access
 * @property int    $pub_recurse
 * @property int    $admin_access
 * @property int    $admin_recurse
 * @property int    $ordering
 * @property int    $published
 * @property string $channels
 * @property int    $checked_out
 * @property string $checked_out_time
 * @property int    $review
 * @property int    $allow_anonymous
 * @property int    $post_anonymous
 * @property int    $hits
 * @property string $description
 * @property string $headerdesc
 * @property string $class_sfx
 * @property int    $allow_polls
 * @property string $topic_ordering
 * @property string $iconset
 * @property int    $numTopics
 * @property int    $numPosts
 * @property int    $last_topic_id
 * @property int    $last_post_id
 * @property int    $last_post_time
 * @property string $params
 * @property string $topictemplate
 * @property string $sectionheaderdesc
 * @property int    $allow_ratings
 *
 * @since   Kunena 6.0
 */
class KunenaForumCategory extends KunenaDatabaseObject
{
	/**
	 * @var     array
	 * @since   Kunena 6.0
	 */
	protected static $actions = [
		'none'                              => [],
		'read'                              => ['Read'],
		'subscribe'                         => ['Read', 'CatSubscribe', 'NotBanned', 'NotSection'],
		'moderate'                          => ['Read', 'NotBanned', 'Moderate'],
		'admin'                             => ['NotBanned', 'Admin'],
		'topic.read'                        => ['Read'],
		'topic.create'                      => ['Read', 'GuestWrite', 'NotBanned', 'NotSection', 'Unlocked', 'Channel'],
		'topic.reply'                       => ['Read', 'GuestWrite', 'NotBanned', 'NotSection', 'Unlocked'],
		'topic.edit'                        => ['Read', 'NotBanned', 'Unlocked'],
		'topic.move'                        => ['Read', 'NotBanned', 'Moderate', 'Channel'],
		'topic.approve'                     => ['Read', 'NotBanned', 'Moderate'],
		'topic.delete'                      => ['Read', 'NotBanned', 'Moderate'],
		'topic.undelete'                    => ['Read', 'NotBanned', 'Moderate'],
		'topic.permdelete'                  => ['Read', 'NotBanned', 'Moderate'],
		'topic.favorite'                    => ['Read', 'NotBanned', 'Favorite'],
		'topic.subscribe'                   => ['Read', 'NotBanned', 'Subscribe'],
		'topic.sticky'                      => ['Read', 'NotBanned', 'Moderate'],
		'topic.lock'                        => ['Read', 'NotBanned', 'Moderate'],
		'topic.poll.read'                   => ['Read', 'Poll'],
		'topic.poll.create'                 => ['Read', 'GuestWrite', 'NotBanned', 'Unlocked', 'Poll'],
		'topic.poll.edit'                   => ['Read', 'NotBanned', 'Unlocked', 'Poll', 'Vote'],
		'topic.poll.delete'                 => ['Read', 'NotBanned', 'Unlocked', 'Poll', 'Vote'],
		'topic.poll.vote'                   => ['Read', 'NotBanned', 'Unlocked', 'Poll', 'Vote'],
		'topic.post.read'                   => ['Read'],
		'topic.post.reply'                  => ['Read', 'GuestWrite', 'NotBanned', 'NotSection', 'Unlocked'],
		'topic.post.thankyou'               => ['Read', 'NotBanned'], 'Unlocked',
		'topic.post.unthankyou'             => ['Read', 'NotBanned'], 'Unlocked',
		'topic.post.edit'                   => ['Read', 'NotBanned', 'Unlocked'],
		'topic.post.move'                   => ['Read', 'NotBanned', 'Moderate', 'Channel'],
		'topic.post.approve'                => ['Read', 'NotBanned', 'Moderate'],
		'topic.post.delete'                 => ['Read', 'NotBanned', 'Unlocked'],
		'topic.post.undelete'               => ['Read', 'NotBanned', 'Moderate'],
		'topic.post.permdelete'             => ['Read', 'NotBanned', 'Admin'],
		'topic.post.attachment.read'        => ['Read'],
		'topic.post.attachment.createimage' => ['Read', 'GuestWrite', 'NotBanned', 'Unlocked', 'Upload'],
		'topic.post.attachment.createfile'  => ['Read', 'GuestWrite', 'NotBanned', 'Unlocked', 'Upload'],
		'topic.post.attachment.delete'      => ['NotBanned'],
		// TODO: In the future we might want to restrict this: array('Read', 'NotBanned', 'Unlocked'),
	];

	/**
	 * @var     null
	 * @since   Kunena 6.0
	 */
	public $id = null;

	/**
	 * @var     integer
	 * @since   Kunena 6.0
	 */
	public $level = 0;

	/**
	 * @var     array
	 * @since   Kunena 6.0
	 */
	protected $authorised = [];

	/**
	 * @var     null
	 * @since   Kunena 6.0
	 */
	protected $_aliases = null;

	/**
	 * @var     mixed|null
	 * @since   Kunena 6.0
	 */
	protected $_alias = null;

	/**
	 * @var     KunenaForumCategory[]
	 * @since   Kunena 6.0
	 */
	protected $_channels = false;

	/**
	 * @var     boolean
	 * @since   Kunena 6.0
	 */
	protected $_topics = false;

	/**
	 * @var     boolean
	 * @since   Kunena 6.0
	 */
	protected $_posts = false;

	/**
	 * @var     boolean
	 * @since   Kunena 6.0
	 */
	protected $_lastcat = false;

	/**
	 * @var     array
	 * @since   Kunena 6.0
	 */
	protected $_authcache = [];

	/**
	 * @var     array
	 * @since   Kunena 6.0
	 */
	protected $_authfcache = [];

	/**
	 * @var     integer
	 * @since   Kunena 6.0
	 */
	protected $_new = null;

	/**
	 * @var     string
	 * @since   Kunena 6.0
	 */
	protected $_table = 'KunenaCategories';

	/**
	 * @var     string
	 * @since   Kunena 6.0
	 */
	protected $sectionheaderdesc;

	/**
	 * @var     integer
	 * @since   Kunena 6.0
	 */
	protected $hold;

	/**
	 * @param   mixed|array  $properties  properties
	 *
	 * @since   Kunena 6.0
	 */
	public function __construct($properties = null)
	{
		if (!empty($this->id))
		{
			$this->_exists = true;
		}
		elseif ($properties !== null)
		{
			$this->setProperties($properties);
		}

		$registry = new Registry;

		if (!empty($this->params))
		{
			$registry->loadString($this->params);
		}

		$this->params = $registry;

		if (!$this->_name)
		{
			$this->_name = get_class($this);
		}

		$this->_alias = $this->get('alias', '');

		if (!empty($this->description))
		{
			$this->sectionheaderdesc = $this->description;
		}
		else
		{
			$this->sectionheaderdesc = '';
		}
	}

	/**
	 * Returns the global KunenaForumCategory object.
	 *
	 * @param   null  $identifier  The category id to load.
	 * @param   bool  $reload      Force reload from the database.
	 *
	 * @return  KunenaForumCategory
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public static function getInstance($identifier = null, $reload = false)
	{
		return KunenaForumCategoryHelper::get($identifier, $reload);
	}

	/**
	 * Returns list of children of this category.
	 *
	 * @param   int  $levels  How many levels to search.
	 *
	 * @return  array    List of KunenaForumCategory objects.
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  null
	 */
	public function getChildren($levels = 0)
	{
		return KunenaForumCategoryHelper::getChildren($this->id, $levels);
	}

	/**
	 * Returns object containing user information from this category.
	 *
	 * @param   mixed  $user  user
	 *
	 * @return  KunenaForumCategoryUser
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function getUserInfo($user = null)
	{
		return KunenaForumCategoryUserHelper::get($this->id, $user);
	}

	/**
	 * Subscribe / Unsubscribe user to this category.
	 *
	 * @param   boolean  $value  True for subscribe, false for unsubscribe.
	 * @param   mixed    $user   user
	 *
	 * @return  boolean
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function subscribe($value = true, $user = null)
	{
		$usercategory             = KunenaForumCategoryUserHelper::get($this->id, $user);
		$usercategory->subscribed = (int) $value;

		if (!$usercategory->params)
		{
			$usercategory->params = '';
		}

		try
		{
			$usercategory->save();
		}
		catch (Exception $e)
		{
			KunenaError::displayDatabaseError($e);
		}

		return true;
	}

	/**
	 * Returns new topics count from this category for current user.
	 *
	 * @param   mixed  $count  Internal parameter to set new count.
	 *
	 * @return integer  New topics count.
	 *
	 * @since   Kunena 6.0
	 *
	 * @todo  Currently new topics needs to be calculated manually, make it automatic.
	 */
	public function getNewCount($count = null)
	{
		if ($count !== null)
		{
			$this->_new = $count;
		}

		return $this->_new;
	}

	/**
	 * @return  string
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function getIcon()
	{
		return KunenaFactory::getTemplate()->getCategoryIcon($this);
	}

	/**
	 * @param   mixed  $category  Fake category (or null).
	 * @param   bool   $xhtml     True if URL needs to be escaped for XHTML.
	 *
	 * @return  string
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function getUrl($category = null, $xhtml = true)
	{
		$category = $category ? KunenaForumCategoryHelper::get($category) : $this;

		return KunenaRoute::getCategoryUrl($category, $xhtml);
	}

	/**
	 * @param   bool  $xhtml  xhtml
	 *
	 * @return  boolean|null
	 *
	 * @since   Kunena
	 *
	 * @throws  null
	 * @throws  Exception
	 */
	public function getNewTopicUrl($xhtml = true)
	{
		if (!$this->getNewTopicCategory()->exists())
		{
			return false;
		}

		$catid = $this->id ? "&catid={$this->id}" : '';

		return KunenaRoute::_("index.php?option=com_kunena&view=topic&layout=create{$catid}", $xhtml);
	}

	/**
	 * @param   mixed  $user  user
	 *
	 * @return  KunenaForumCategory
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  null
	 */
	public function getNewTopicCategory($user = null)
	{
		foreach ($this->getChannels() as $category)
		{
			if ($category->isAuthorised('topic.create', $user))
			{
				return $category;
			}
		}

		$categories = KunenaForumCategoryHelper::getChildren(intval($this->id), -1, ['action' => 'topic.create']);

		if ($categories)
		{
			foreach ($categories as $category)
			{
				if ($category->isAuthorised('topic.create', null))
				{
					return $category;
				}
			}
		}

		return new KunenaForumCategory;
	}

	/**
	 * @param   string  $action  action
	 *
	 * @return  KunenaForumCategory|KunenaForumCategory[]
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  null
	 * @throws  Exception
	 */
	public function getChannels($action = 'read')
	{
		KUNENA_PROFILER ? KunenaProfiler::instance()->start('function ' . __CLASS__ . '::' . __FUNCTION__ . '()') : null;

		if ($this->_channels === false)
		{
			$this->_channels['none'] = [];

			if (empty($this->channels) || $this->channels == $this->id)
			{
				// No channels defined
				$this->_channels['none'][$this->id] = $this;
			}
			else
			{
				// Fetch all channels
				$ids = array_flip(explode(',', $this->channels));

				if (isset($ids[0]) || isset($ids['THIS']))
				{
					// Handle current category
					$this->_channels['none'][$this->id] = $this;
				}

				if (!empty($ids))
				{
					// More category channels
					$this->_channels['none'] += KunenaForumCategoryHelper::getCategories(array_keys($ids), null, 'none');
				}

				if (isset($ids['CHILDREN']))
				{
					// Children category channels
					$this->_channels['none'] += KunenaForumCategoryHelper::getChildren($this->id, 1, [$action => 'none']);
				}
			}
		}

		if (!isset($this->_channels[$action]))
		{
			$this->_channels[$action] = [];

			foreach ($this->_channels['none'] as $channel)
			{
				if (($channel->id == $this->id && $action == 'read') || $channel->isAuthorised($action, null, false))
				{
					$this->_channels[$action][$channel->id] = $channel;
				}
			}
		}

		KUNENA_PROFILER ? KunenaProfiler::instance()->stop('function ' . __CLASS__ . '::' . __FUNCTION__ . '()') : null;

		return $this->_channels[$action];
	}

	/**
	 * Returns true if user is authorised to do the action.
	 *
	 * @param   string      $action  action
	 * @param   KunenaUser  $user    user
	 *
	 * @return  boolean
	 *
	 * @since   Kunena 4.0
	 *
	 * @throws  null
	 */
	public function isAuthorised($action = 'read', KunenaUser $user = null)
	{
		if (KunenaFactory::getConfig()->read_only)
		{
			// Special case to ignore authorisation.
			if ($action != 'read')
			{
				return false;
			}
		}

		return !$this->tryAuthorise($action, $user, false);
	}

	/**
	 * Throws an exception if user isn't authorised to do the action.
	 *
	 * @param   string      $action  action
	 * @param   KunenaUser  $user    user
	 * @param   bool        $throw   throw
	 *
	 * @return  KunenaExceptionAuthorise|boolean
	 *
	 * @since   Kunena 4.0
	 *
	 * @throws  null
	 */
	public function tryAuthorise($action = 'read', KunenaUser $user = null, $throw = true)
	{
		// Special case to ignore authorisation.
		if ($action == 'none')
		{
			return false;
		}

		// Load user if not given.
		if ($user === null)
		{
			$user = KunenaUserHelper::getMyself();
		}

		// Optimise read access check.
		if ($action == 'read')
		{
			$exception = $this->authoriseRead($user);

			if ($throw && $exception)
			{
				throw new $exception;
			}

			return $exception;
		}

		// Use local authentication cache to speed up the authentication calls.
		if (empty($this->_authcache[$user->userid][$action]))
		{
			// Unknown action - throw invalid argument exception.
			if (!isset(self::$actions[$action]))
			{
				throw new InvalidArgumentException(Text::sprintf('COM_KUNENA_LIB_AUTHORISE_INVALID_ACTION', $action), 500);
			}

			// Load custom authorisation from the plugins (except for admins and moderators).
			if (!$user->isModerator($this) && !isset($this->authorised[$user->userid]))
			{
				$this->authorised[$user->userid] = KunenaAccess::getInstance()->authoriseActions($this, $user->userid);
			}

			if (isset($this->authorised[$user->userid][$action])
				&& $this->authorised[$user->userid][$action] === false
			)
			{
				// Plugin forces authorisation to fail.
				// TODO: allow plugin to customise the error.
				$this->_authcache[$user->userid][$action] = new KunenaExceptionAuthorise(Text::_('COM_KUNENA_NO_ACCESS'), $user->userid ? 403 : 401);
			}
			else
			{
				// Do the authorisation.
				$this->_authcache[$user->userid][$action] = null;

				foreach (self::$actions[$action] as $function)
				{
					if (!isset($this->_authfcache[$user->userid][$function]))
					{
						$authFunction                                = 'authorise' . $function;
						$this->_authfcache[$user->userid][$function] = $this->$authFunction($user);
					}

					$error = $this->_authfcache[$user->userid][$function];

					if ($error)
					{
						$this->_authcache[$user->userid][$action] = $error;
						break;
					}
				}
			}
		}

		$exception = $this->_authcache[$user->userid][$action];

		// Throw or return the exception.
		if ($throw && $exception)
		{
			throw $exception;
		}

		return $exception;
	}

	/**
	 * @param   KunenaUser  $user  user
	 *
	 * @return  KunenaExceptionAuthorise|void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	protected function authoriseRead(KunenaUser $user)
	{
		static $catids = false;

		if ($catids === false)
		{
			$catids = KunenaAccess::getInstance()->getAllowedCategories($user);
		}

		// Checks if user can read category
		if (!$this->exists())
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_NO_ACCESS'), 404);
		}

		if (empty($catids[$this->id]))
		{
			if ($user->exists())
			{
				return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_NO_ACCESS'), 403);
			}
			else
			{
				return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_NO_ACCESS'), 401);
			}
		}

		return;
	}

	/**
	 * @param   bool  $children  children
	 * @param   bool  $xhtml     xhtml
	 *
	 * @return  boolean|null
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  null
	 * @throws  Exception
	 */
	public function getMarkReadUrl($children = false, $xhtml = true)
	{
		if (!KunenaUserHelper::getMyself()->exists())
		{
			return false;
		}

		$children = $children ? "&children=1" : '';
		$catid    = $this->id ? "&catid={$this->id}" : '';
		$token    = '&' . Session::getFormToken() . '=1';

		return KunenaRoute::_("index.php?option=com_kunena&view=category&task=markread{$catid}{$children}{$token}", $xhtml);
	}

	/**
	 * Method which  return the RSS feed URL for the actual category
	 *
	 * @param   bool|string  $xhtml  xhtml
	 *
	 * @return  boolean|void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  null
	 * @throws  Exception
	 */
	public function getRSSUrl($xhtml = true)
	{
		if (KunenaFactory::getConfig()->enablerss)
		{
			$params = '&catid=' . (int) $this->id;

			if (CMSApplication::getInstance('site')->get('sef_suffix'))
			{
				return KunenaRoute::_("/index.php?option=com_kunena&view=rss{$params}") . '?format=feed&type=rss';
			}
			else
			{
				return KunenaRoute::_("index.php?option=com_kunena&view=rss{$params}?format=feed&type=rss", $xhtml);
			}
		}

		return;
	}

	/**
	 * @param   mixed     $category  Fake category (or null).
	 * @param   int|null  $action    Limitstart.
	 *
	 * @return  Uri
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function getUri($category = null, $action = null)
	{
		if (!$category)
		{
			$category = $this;
		}
		else
		{
			$category = KunenaForumCategoryHelper::get($category);
		}

		$uri = Uri::getInstance("index.php?option=com_kunena&view=category&catid={$category->id}");

		if ((string) $action === (string) (int) $action)
		{
			$uri->setVar('limitstart', $action);
		}

		return $uri;
	}

	/**
	 * @param   string  $field  Field to be displayed.
	 *
	 * @return  integer|string
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function displayField($field)
	{
		switch ($field)
		{
			case 'id':
				return intval($this->id);
			case 'name':
				return KunenaHtmlParser::parseText($this->name, '', 'category_name');
			case 'icon':
				return KunenaHtmlParser::parseText($this->name, '', 'category_icon');
			case 'description':
				return KunenaHtmlParser::parseBBCode($this->$field, '', '', '', 'category_description');
			case 'topictemplate':
				return KunenaHtmlParser::parseBBCode($this->$field, '', '', '', 'category_topictemplate');
			case 'headerdesc':
				return KunenaHtmlParser::parseBBCode($this->$field, '', '', '', 'category_headerdesc');
		}

		return '';
	}

	/**
	 * @return  $this
	 *
	 * @since   Kunena 6.0
	 */
	public function getCategory()
	{
		return $this;
	}

	/**
	 * @return  array  Array of Kunena aliases.
	 *
	 * @since   Kunena 6.0
	 */
	public function getAliases()
	{
		if (!isset($this->_aliases))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('*')
				->from($db->quoteName('#__kunena_aliases'))
				->where($db->quoteName('type') . ' = ' . $db->quote('catid'))
				->andWhere($db->quoteName('item') . ' = ' . $db->quote($this->id));
			$db->setQuery($query);
			$this->_aliases = (array) $db->loadObjectList('alias');
		}

		return $this->_aliases;
	}

	/**
	 * @param   string  $alias  alias
	 *
	 * @return  boolean
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function deleteAlias($alias)
	{
		// Do not delete valid alias.
		if (StringHelper::strtolower($this->alias) == StringHelper::strtolower($alias))
		{
			return false;
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->delete()
			->from($db->quoteName('#__kunena_aliases'))
			->where($db->quoteName('type') . ' = ' . $db->quote('catid'))
			->andWhere($db->quoteName('item') . ' = ' . $db->quote($this->id))
			->andWhere($db->quoteName('alias') . ' = ' . $db->quote($alias));
		$db->setQuery($query);

		try
		{
			$db->execute();
		}
		catch (ExecutionFailureException $e)
		{
			KunenaError::displayDatabaseError($e);
		}

		return (bool) $db->getAffectedRows();
	}

	/**
	 * Get published state in text.
	 *
	 * @return  string
	 *
	 * @since   Kunena 4.0
	 */
	public function getState()
	{
		switch ($this->hold)
		{
			case 0:
				return 'published';
			case 1:
				return 'unapproved';
			case 2:
			case 3:
				return 'deleted';
		}

		return 'unknown';
	}

	/**
	 * @return  boolean
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function getTopics()
	{
		$this->buildInfo();

		return $this->_topics;
	}

	/**
	 * @return  void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	protected function buildInfo()
	{
		if ($this->_topics !== false)
		{
			return;
		}

		$this->_topics  = 0;
		$this->_posts   = 0;
		$this->_lastcat = $this;

		$categories[$this->id] = $this;

		$categories += $this->getChannels();
		$categories += KunenaForumCategoryHelper::getChildren($this->id);

		foreach ($categories as $category)
		{
			$category->buildInfo();
			$lastCategory  = $category->getLastCategory();
			$this->_topics += $category->_topics ? $category->_topics : max($category->numTopics, 0);
			$this->_posts  += $category->_posts ? $category->_posts : max($category->numPosts, 0);

			if ($lastCategory->last_post_time && $this->_lastcat->last_post_time < $lastCategory->last_post_time)
			{
				$this->_lastcat = $lastCategory;
			}
		}
	}

	/**
	 * @return  boolean|KunenaForumCategory
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function getLastCategory()
	{
		$this->buildInfo();

		return $this->_lastcat;
	}

	/**
	 * @return  boolean
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function getPosts()
	{
		$this->buildInfo();

		return $this->_posts;
	}

	/**
	 * @return  integer
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function getReplies()
	{
		$this->buildInfo();

		return max($this->_posts - $this->_topics, 0);
	}

	/**
	 * @return  KunenaForumTopic
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function getLastTopic()
	{
		return KunenaForumTopicHelper::get($this->getLastCategory()->last_topic_id);
	}

	/**
	 * @param   array  $fields      fields
	 * @param   mixed  $user        user
	 * @param   array  $safefields  safefields
	 *
	 * @return  array
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  null
	 */
	public function newTopic(array $fields = null, $user = null, array $safefields = null)
	{
		$catid           = isset($safefields['category_id']) ? $safefields['category_id'] : $this->getNewTopicCategory($user)->id;
		$user            = KunenaUserHelper::get($user);
		$message         = new KunenaForumMessage;
		$message->catid  = $catid;
		$message->name   = $user->getName('');
		$message->userid = $user->userid;
		$message->ip     = !empty(KunenaUserHelper::getUserIp()) ? KunenaUserHelper::getUserIp() : '';
		$message->hold   = $this->review ? (int) !$this->isAuthorised('moderate', $user) : 0;

		if ($safefields)
		{
			$message->bind($safefields);
		}

		if ($fields)
		{
			$message->bind($fields, ['name', 'email', 'subject', 'message'], true);
		}

		$topic              = new KunenaForumTopic;
		$topic->category_id = $catid;
		$topic->hold        = KunenaForum::TOPIC_CREATION;
		$topic->rating      = 0;
		$topic->params      = '';

		if ($safefields)
		{
			$topic->bind($safefields);
		}

		if ($fields)
		{
			$topic->bind($fields, ['subject', 'icon_id'], true);
		}

		$message->setTopic($topic);

		return [$topic, $message];
	}

	/**
	 * @return  KunenaForumCategory
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function getParent()
	{
		return KunenaForumCategoryHelper::get(intval($this->parent_id));
	}

	/**
	 * Get users, who can administrate this category.
	 *
	 * @param   bool  $includeGlobal  include global
	 *
	 * @return  array
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function getAdmins($includeGlobal = true)
	{
		$access   = KunenaAccess::getInstance();
		$userlist = [];

		if (!empty($this->catid))
		{
			$userlist = $access->getAdmins($this->catid);
		}

		if ($includeGlobal)
		{
			$userlist += $access->getAdmins();
		}

		return $userlist;
	}

	/**
	 * Get users, who can moderate this category.
	 *
	 * @param   bool  $includeGlobal  include global
	 * @param   bool  $objects        objects
	 *
	 * @return  array
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function getModerators($includeGlobal = true, $objects = true)
	{
		$access   = KunenaAccess::getInstance();
		$userlist = [];

		if (!empty($this->id))
		{
			$userlist += $access->getModerators($this->id);
		}

		if ($includeGlobal)
		{
			$userlist += $access->getModerators();
		}

		if (empty($userlist))
		{
			return $userlist;
		}

		$userlist = array_keys($userlist);

		return $objects ? KunenaUserHelper::loadUsers($userlist) : array_combine($userlist, $userlist);
	}

	/**
	 * Add moderator to this category.
	 *
	 * @param   mixed  $user  user
	 *
	 * @return  boolean
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 * @example if ($category->isAuthorised('admin')) $category->addModerator($user);
	 */
	public function addModerator($user = null)
	{
		return $this->setModerator($user, true);
	}

	/**
	 * Add or remove moderator from this category.
	 *
	 * @param   mixed  $user   user
	 * @param   bool   $value  value
	 *
	 * @return  boolean
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 * @example if ($category->isAuthorised('admin')) $category->setModerator($user, true);
	 */
	public function setModerator($user = null, $value = false)
	{
		return KunenaAccess::getInstance()->setModerator($this, $user, $value);
	}

	/**
	 * Add multiple moderators to this category.
	 *
	 * @param   array  $users  users
	 *
	 * @return  void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 * @example if ($category->isAuthorised('admin')) $category->addModerators(array($user1, $user2, $user3));
	 */
	public function addModerators($users = [])
	{
		if (empty($users))
		{
			return;
		}

		foreach ($users as $user)
		{
			$user_inst = KunenaUserHelper::get($user);
			$this->setModerator($user_inst, true);
		}
	}

	/**
	 * Remove moderator from this category.
	 *
	 * @param   mixed  $user  user
	 *
	 * @return  boolean
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 * @example if ($category->isAuthorised('admin')) $category->removeModerator($user);
	 */
	public function removeModerator($user = null)
	{
		return $this->setModerator($user, false);
	}

	/**
	 * @see     KunenaDatabaseObject::bind()
	 *
	 * @param   array  $src      src
	 * @param   array  $fields   fields
	 * @param   bool   $include  include
	 *
	 * @return  boolean
	 *
	 * @since   Kunena 6.0
	 */
	public function bind(array $src = null, array $fields = null, $include = false)
	{
		if (isset($src['channels']) && is_array($src['channels']))
		{
			$src['channels'] = implode(',', $src['channels']);
		}

		$result = parent::bind($src, $fields, $include);

		if (!($this->params instanceof Registry))
		{
			$registry = new Registry;

			if (is_array($this->params))
			{
				$registry->loadArray($this->params);
			}
			else
			{
				$registry->loadString($this->params);
			}

			$this->params = $registry;
		}

		return $result;
	}

	/**
	 * @see     KunenaDatabaseObject::load()
	 *
	 * @param   null  $id  id
	 *
	 * @return  boolean
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function load($id = null)
	{
		$exists = parent::load($id);

		if (!$this->_saving)
		{
			$this->_alias = $this->get('alias');
		}

		$registry = new Registry;

		if ($this->params)
		{
			$registry->loadString($this->params);
		}

		$this->params = $registry;

		// Register category if it exists
		if ($exists)
		{
			KunenaForumCategoryHelper::register($this);
		}

		return $exists;
	}

	/**
	 * @see     KunenaDatabaseObject::check()
	 *
	 * @return  mixed
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function check()
	{
		$this->alias = trim($this->alias);

		if (empty($this->alias))
		{
			$this->alias = $this->name;
		}

		if ($this->alias != $this->_alias)
		{
			$this->alias = KunenaRoute::stringURLSafe($this->alias);

			try
			{
				$this->checkAlias($this->alias);
			}
			catch (Exception $e)
			{
				KunenaError::displayDatabaseError($e);

				return false;
			}
		}

		return true;
	}

	/**
	 * @param   string  $alias  alias
	 *
	 * @return  boolean|string
	 *
	 * @since   Kunena 6.0
	 */
	public function checkAlias($alias)
	{
		// Check if category is already using the alias.
		if ($this->_alias && $this->_alias == $alias)
		{
			return true;
		}

		// Check if alias is valid in current configuration.
		if (KunenaRoute::stringURLSafe($alias) != $alias)
		{
			return false;
		}

		$item = KunenaRoute::resolveAlias($alias);

		// Is alias free to be used?
		if (!$item)
		{
			return 'insert';
		}

		// Fail if alias is reserved or used by another category.
		if (empty($item['catid']) || $item['catid'] != $this->id)
		{
			return false;
		}

		return 'update';
	}

	/**
	 * Purge old topics from this category. Removes topics from the database.
	 *
	 * @param   int    $time    time
	 * @param   array  $params  params
	 * @param   int    $limit   limit
	 *
	 * @return  integer  Number of purged topics.
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function purge($time, $params = [], $limit = 1000)
	{
		// FIXME: why time isn't used?
		if (!$this->exists())
		{
			return 0;
		}

		$where = isset($params['where']) ? (string) $params['where'] : '';

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))
			->from($db->quoteName('#__kunena_topics', 'tt'))
			->where('tt.category_id = ' . $this->id . ' ' . $where)
			->order('tt.last_post_time ASC');
		$query->setLimit($limit);
		$db->setQuery($query);

		try
		{
			$ids = $db->loadColumn();
		}
		catch (ExecutionFailureException $e)
		{
			KunenaError::displayDatabaseError($e);
		}

		if (empty($ids))
		{
			return 0;
		}

		$count = KunenaForumTopicHelper::delete($ids);

		KunenaUserHelper::recount();
		KunenaForumCategoryHelper::recount($this->id);
		KunenaAttachmentHelper::cleanup();

		return $count;
	}

	/**
	 * Trash old topics in this category. Changes topic state to deleted.
	 *
	 * @param   int    $time    time
	 * @param   array  $params  params
	 * @param   int    $limit   limit
	 *
	 * @return  integer  Number of trashed topics.
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function trash($time, $params = [], $limit = 1000)
	{
		// FIXME: why time isn't used?
		if (!$this->exists())
		{
			return 0;
		}

		$where = isset($params['where']) ? (string) $params['where'] : '';

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))
			->from($db->quoteName('#__kunena_topics', 'tt'))
			->where('tt.category_id = ' . $this->id . ' AND tt.hold!=2 ' . $where)
			->order('tt.last_post_time ASC');
		$query->setLimit($limit);
		$db->setQuery($query);

		try
		{
			$ids = $db->loadColumn();
		}
		catch (ExecutionFailureException $e)
		{
			KunenaError::displayDatabaseError($e);
		}

		if (empty($ids))
		{
			return 0;
		}

		$count = KunenaForumTopicHelper::trash($ids);

		KunenaUserHelper::recount();
		KunenaForumCategoryHelper::recount($this->id);

		return $count;
	}

	/**
	 * Delete this category and all related information from the database.
	 *
	 * @return  boolean  True on success
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function delete()
	{
		if (!$this->exists())
		{
			return true;
		}

		if (!parent::delete())
		{
			return false;
		}

		$access = KunenaAccess::getInstance();
		$access->clearCache();

		$db        = Factory::getDBO();
		$queries[] = "DELETE FROM #__kunena_aliases WHERE type='catid' AND item={$db->quote($this->id)}";

		// Delete user topics
		$queries[] = "DELETE FROM #__kunena_user_topics WHERE category_id={$db->quote($this->id)}";

		// Delete user categories
		$queries[] = "DELETE FROM #__kunena_user_categories WHERE category_id={$db->quote($this->id)}";

		// Delete user read
		$queries[] = "DELETE FROM #__kunena_user_read WHERE category_id={$db->quote($this->id)}";

		// Delete thank yous
		$queries[] = "DELETE t FROM #__kunena_thankyou AS t INNER JOIN #__kunena_messages AS m ON m.id=t.postid WHERE m.catid={$db->quote($this->id)}";

		// Delete poll users
		$queries[] = "DELETE p FROM #__kunena_polls_users AS p INNER JOIN #__kunena_topics AS tt ON tt.poll_id=p.pollid WHERE tt.category_id={$db->quote($this->id)} AND tt.moved_id=0";

		// Delete poll options
		$queries[] = "DELETE p FROM #__kunena_polls_options AS p INNER JOIN #__kunena_topics AS tt ON tt.poll_id=p.pollid WHERE tt.category_id={$db->quote($this->id)} AND tt.moved_id=0";

		// Delete polls
		$queries[] = "DELETE p FROM #__kunena_polls AS p INNER JOIN #__kunena_topics AS tt ON tt.poll_id=p.id WHERE tt.category_id={$db->quote($this->id)} AND tt.moved_id=0";

		// Delete messages
		$queries[] = "DELETE m, t FROM #__kunena_messages AS m INNER JOIN #__kunena_messages_text AS t ON m.id=t.mesid WHERE m.catid={$db->quote($this->id)}";

		// TODO: delete attachments
		// Delete topics
		$queries[] = "DELETE FROM #__kunena_topics WHERE category_id={$db->quote($this->id)}";

		foreach ($queries as $query)
		{
			$db->setQuery($query);

			try
			{
				$db->execute();
			}
			catch (ExecutionFailureException $e)
			{
				KunenaError::displayDatabaseError($e);
			}
		}

		KunenaUserHelper::recount();
		KunenaForumMessageThankyouHelper::recount();

		$this->id = null;
		KunenaForumCategoryHelper::register($this);

		return true;
	}

	/**
	 * Method to check out the KunenaForumCategory object.
	 *
	 * @param   integer  $who  who
	 *
	 * @return  boolean  True if checked out by somebody else.
	 *
	 * @since   Kunena 1.6
	 */
	public function checkout($who)
	{
		if (!$this->_exists)
		{
			return false;
		}

		// Create the user table object
		$table = $this->getTable();
		$table->bind($this->getProperties());
		$table->exists($this->_exists);
		$result = $table->checkout($who);

		// Assuming all is well at this point lets bind the data
		$params = $this->params;
		$this->setProperties($table->getProperties());
		$this->params = $params;

		return $result;
	}

	/**
	 * Method to check in the KunenaForumCategory object.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   Kunena 1.6
	 */
	public function checkin()
	{
		if (!$this->_exists)
		{
			return true;
		}

		// Create the user table object
		$table = $this->getTable();
		$table->bind($this->getProperties());
		$table->exists($this->_exists);
		$result = $table->checkin();

		// Assuming all is well at this point lets bind the data
		$params = $this->params;
		$this->setProperties($table->getProperties());
		$this->params = $params;

		// $cache = Factory::getCache('com_kunena', 'output');
		// $cache->clean('categories');

		return $result;
	}

	/**
	 * Method to check if an item is checked out.
	 *
	 * @param   string  $with  with
	 *
	 * @return  boolean
	 *
	 * @since   Kunena 1.6
	 */
	public function isCheckedOut($with)
	{
		if (!$this->_exists)
		{
			return false;
		}

		// Create the user table object
		$table = $this->getTable();
		$table->bind($this->getProperties());
		$table->exists($this->_exists);
		$result = $table->isCheckedOut($with);

		return $result;
	}

	/**
	 * @param   KunenaForumTopic  $topic       topic
	 * @param   int               $topicdelta  topicdelta
	 * @param   int               $postdelta   postdelta
	 *
	 * @return  boolean
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function update($topic, $topicdelta = 0, $postdelta = 0)
	{
		if (!$topic->id)
		{
			return false;
		}

		$update = false;

		if ($topicdelta || $postdelta)
		{
			// Update topic and post count
			$this->numTopics += (int) $topicdelta;
			$this->numPosts  += (int) $postdelta;
			$update          = true;
		}

		if ($topic->exists() && $topic->hold == 0 && $topic->moved_id == 0 && $topic->category_id == $this->id
			&& ($this->last_post_time < $topic->last_post_time || ($this->last_post_time == $topic->last_post_time && $this->last_post_id <= $topic->last_post_id))
		)
		{
			// If topic has new post or last topic changed, we need to update cache
			$this->last_topic_id  = $topic->id;
			$this->last_post_id   = $topic->last_post_id;
			$this->last_post_time = $topic->last_post_time;
			$update               = true;
		}
		elseif ($this->last_topic_id == $topic->id)
		{
			// If last topic/post got moved or deleted, we need to find last post
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('*')
				->from($db->quoteName('#__kunena_topics'))
				->where($db->quoteName('category_id') . ' = ' . $db->quote($this->id))
				->andWhere($db->quoteName('hold') . ' = 0')
				->andWhere($db->quoteName('moved_id') . ' = 0')
				->order([$db->quoteName('moved_id') . ' DESC', $db->quoteName('last_post_id') . ' DESC']);
			$query->setLimit(1);
			$db->setQuery($query);

			try
			{
				$topic = $db->loadObject();
			}
			catch (ExecutionFailureException $e)
			{
				KunenaError::displayDatabaseError($e);
			}

			if ($topic)
			{
				$this->last_topic_id  = $topic->id;
				$this->last_post_id   = $topic->last_post_id;
				$this->last_post_time = $topic->last_post_time;
				$update               = true;
			}
			else
			{
				$this->numTopics      = 0;
				$this->numPosts       = 0;
				$this->last_topic_id  = 0;
				$this->last_post_id   = 0;
				$this->last_post_time = 0;
				$update               = true;
			}
		}

		if (!$update)
		{
			return true;
		}

		// TODO: remove this hack...
		$this->_noreorder = true;

		return $this->save();
	}

	/**
	 * Get if the user has subscribed on this category.
	 *
	 * @param   mixed  $userid  userid
	 *
	 * @return  boolean
	 *
	 * @since   Kunena 2.0
	 *
	 * @throws  Exception
	 */
	public function getSubscribed($userid = null)
	{
		if (!$this->exists())
		{
			return false;
		}

		if (!$userid)
		{
			return false;
		}

		$usercategory = KunenaForumCategoryUserHelper::get($this->id, $userid);

		return (bool) $usercategory->subscribed;
	}

	/**
	 * @param   int  $count  count
	 *
	 * @return  integer
	 *
	 * @since   Kunena 5.0.13
	 */
	public function totalCount($count)
	{
		if ($count)
		{
			if ($count > 1)
			{
				return Text::plural('COM_KUNENA_X_TOPICS_MORE', $this->formatLargeNumber($count));
			}
			else
			{
				return Text::plural('COM_KUNENA_X_TOPICS_1', $count);
			}
		}

		return Text::_('COM_KUNENA_X_TOPICS_0');
	}

	/**
	 * This function formats a number to n significant digits when above
	 * 10,000. Starting at 10,0000 the out put changes to 10k, starting
	 * at 1,000,000 the output switches to 1m. Both k and m are defined
	 * in the language file. The significant digits are used to limit the
	 * number of digits displayed when in 10k or 1m mode.
	 *
	 * @param   int  $number     Number to be formated
	 * @param   int  $precision  Significant digits for output
	 *
	 * @return  string
	 *
	 * @since   Kunena 6.0
	 */
	public function formatLargeNumber($number, $precision = 3)
	{
		// Do we need to reduce the number of significant digits?
		if ($number >= 10000)
		{
			// Round the number to n significant digits
			$number = round($number, -1 * (log10($number) + 1) + $precision);
		}

		if ($number < 10000)
		{
			$output = $number;
		}
		elseif ($number >= 1000000)
		{
			$output = $number / 1000000 . Text::_('COM_KUNENA_MILLION');
		}
		else
		{
			$output = $number / 1000 . Text::_('COM_KUNENA_THOUSAND');
		}

		return $output;
	}

	/**
	 * @see     KunenaDatabaseObject::saveInternal()
	 *
	 * @return  boolean
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	protected function saveInternal()
	{
		// Reorder categories
		$table = $this->getTable();
		$table->bind($this->getProperties());
		$table->exists($this->_exists);

		// Update alias
		$success = $this->addAlias($this->get('alias'));

		if ($success)
		{
			$this->_alias = $this->alias;
		}

		// TODO: remove this hack...
		if (!isset($this->_noreorder))
		{
			$table->reorder();
			$this->ordering = $table->ordering;
			unset($this->_noreorder);
		}

		// Clear cache
		$access = KunenaAccess::getInstance();
		$access->clearCache();

		KunenaCacheHelper::clear();

		return true;
	}

	/**
	 * @param   string  $alias  alias
	 *
	 * @return  boolean|void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function addAlias($alias)
	{
		if (!$this->exists())
		{
			return false;
		}

		$alias = KunenaRoute::stringURLSafe($alias);

		if (!$alias)
		{
			$alias = $this->id;
		}

		$check = $this->checkAlias($alias);

		// Cannot add alias?
		if ($check === false)
		{
			return false;
		}

		// Did alias change?
		if ($check === true)
		{
			return true;
		}

		$db    = Factory::getDbo();
		$query = "REPLACE INTO #__kunena_aliases (alias, type, item) VALUES ({$db->quote($alias)},'catid',{$db->quote($this->id)})";
		$db->setQuery($query);

		try
		{
			$db->execute();
		}
		catch (ExecutionFailureException $e)
		{
			KunenaError::displayDatabaseError($e);

			return false;
		}
	}

	/**
	 * @param   KunenaUser  $user  user
	 *
	 * @return  KunenaExceptionAuthorise|void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	protected function authoriseNotBanned(KunenaUser $user)
	{
		$banned = $user->isBanned();

		if ($banned)
		{
			$banned = KunenaUserBan::getInstanceByUserid($user->userid, true);

			if (!$banned->isLifetime())
			{
				return new KunenaExceptionAuthorise(Text::sprintf('COM_KUNENA_POST_ERROR_USER_BANNED_NOACCESS_EXPIRY', KunenaDate::getInstance($banned->expiration)->toKunena()), 403);
			}
			else
			{
				return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_POST_ERROR_USER_BANNED_NOACCESS'), 403);
			}
		}

		return;
	}

	/**
	 * @param   KunenaUser  $user  user
	 *
	 * @return  KunenaExceptionAuthorise|void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	protected function authoriseGuestWrite(KunenaUser $user)
	{
		// Check if user is guest and they can create or reply topics
		if ($user->userid == 0 && !KunenaFactory::getConfig()->pubwrite)
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_POST_ERROR_ANONYMOUS_FORBITTEN'), 401);
		}

		return;
	}

	/**
	 * @param   KunenaUser  $user  user
	 *
	 * @return  KunenaExceptionAuthorise|void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	protected function authoriseSubscribe(KunenaUser $user)
	{
		// Check if user is guest and they can create or reply topics
		$config = KunenaFactory::getConfig();

		if (!$config->allowsubscriptions || $config->topic_subscriptions == 'disabled')
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_LIB_CATEGORY_AUTHORISE_FAILED_SUBSCRIPTIONS'), 403);
		}

		if ($user->userid == 0)
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_LIB_CATEGORY_AUTHORISE_FAILED_SUBSCRIPTIONS'), 401);
		}

		return;
	}

	/**
	 * @param   KunenaUser  $user  user
	 *
	 * @return  KunenaExceptionAuthorise|void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	protected function authoriseCatSubscribe(KunenaUser $user)
	{
		// Check if user is guest and they can create or reply topics
		$config = KunenaFactory::getConfig();

		if (!$config->allowsubscriptions || $config->category_subscriptions == 'disabled')
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_LIB_CATEGORY_AUTHORISE_FAILED_SUBSCRIPTIONS'), 403);
		}

		if ($user->userid == 0)
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_LIB_CATEGORY_AUTHORISE_FAILED_SUBSCRIPTIONS'), 401);
		}

		return;
	}

	/**
	 * @param   KunenaUser  $user  user
	 *
	 * @return  KunenaExceptionAuthorise|void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	protected function authoriseFavorite(KunenaUser $user)
	{
		// Check if user is guest and they can create or reply topics
		if (!KunenaFactory::getConfig()->allowfavorites)
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_LIB_CATEGORY_AUTHORISE_FAILED_FAVORITES'), 403);
		}

		if ($user->userid == 0)
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_LIB_CATEGORY_AUTHORISE_FAILED_FAVORITES'), 401);
		}

		return;
	}

	/**
	 * @param   KunenaUser  $user  user
	 *
	 * @return  KunenaExceptionAuthorise|void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	protected function authoriseNotSection(KunenaUser $user)
	{
		// Check if category is not a section
		if ($this->isSection())
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_POST_ERROR_IS_SECTION'), 403);
		}

		return;
	}

	/**
	 * @return  boolean
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public function isSection()
	{
		$this->buildInfo();

		return $this->parent_id == 0;
	}

	/**
	 * @param   KunenaUser  $user  user
	 *
	 * @return  KunenaExceptionAuthorise|void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  null
	 * @throws  Exception
	 */
	protected function authoriseChannel(KunenaUser $user)
	{
		// Check if category is alias
		$channels = $this->getChannels('none');

		if (!isset($channels[$this->id]))
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_POST_ERROR_IS_ALIAS'), 403);
		}

		return;
	}

	/**
	 * @param   KunenaUser  $user  user
	 *
	 * @return  KunenaExceptionAuthorise|void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	protected function authoriseUnlocked(KunenaUser $user)
	{
		// Check that category is not locked or that user is a moderator
		if ($this->locked && (!$user->userid || !$user->isModerator($this)))
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_POST_ERROR_CATEGORY_LOCKED'), 403);
		}

		return;
	}

	/**
	 * @param   KunenaUser  $user  user
	 *
	 * @return  KunenaExceptionAuthorise|void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	protected function authoriseModerate(KunenaUser $user)
	{
		// Check that user is moderator
		if (!$user->userid)
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_POST_NOT_MODERATOR'), 401);
		}

		if (!$user->isModerator($this))
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_POST_NOT_MODERATOR'), 403);
		}

		return;
	}

	/**
	 * @param   KunenaUser  $user  user
	 *
	 * @return  KunenaExceptionAuthorise|void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	protected function authoriseGlobalModerate(KunenaUser $user)
	{
		// Check that user is a global moderator
		if (!$user->userid)
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_POST_NOT_GLOBAL_MODERATOR'), 401);
		}

		if (!$user->isModerator())
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_POST_NOT_GLOBAL_MODERATOR'), 403);
		}

		return;
	}

	/**
	 * @param   KunenaUser  $user  user
	 *
	 * @return  KunenaExceptionAuthorise|void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	protected function authoriseAdmin(KunenaUser $user)
	{
		// Check that user is admin
		if (!$user->userid)
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_MODERATION_ERROR_NOT_ADMIN'), 401);
		}

		if (!$user->isAdmin($this))
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_MODERATION_ERROR_NOT_ADMIN'), 403);
		}

		return;
	}

	/**
	 * @param   KunenaUser  $user  user
	 *
	 * @return  KunenaExceptionAuthorise|void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	protected function authorisePoll(KunenaUser $user)
	{
		// Check if polls are enabled at all
		if (!KunenaFactory::getConfig()->pollenabled)
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_LIB_CATEGORY_AUTHORISE_FAILED_POLLS_DISABLED'), 403);
		}

		// Check if polls are not enabled in this category
		if (!$this->allow_polls)
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_LIB_CATEGORY_AUTHORISE_FAILED_POLLS_NOT_ALLOWED'), 403);
		}

		return;
	}

	/**
	 * @param   KunenaUser  $user  user
	 *
	 * @return  KunenaExceptionAuthorise|void
	 *
	 * @since   Kunena 6.0
	 */
	protected function authoriseVote(KunenaUser $user)
	{
		// Check if user is guest
		if ($user->userid == 0)
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_POLL_NOT_LOGGED'), 401);
		}

		return;
	}

	/**
	 * @param   KunenaUser  $user  user
	 *
	 * @return  KunenaExceptionAuthorise|void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	protected function authoriseUpload(KunenaUser $user)
	{
		// Check if attachments are allowed
		if (KunenaAttachmentHelper::getExtensions($this, $user) === false)
		{
			return new KunenaExceptionAuthorise(Text::_('COM_KUNENA_LIB_CATEGORY_AUTHORISE_FAILED_UPLOAD_NOT_ALLOWED'), 403);
		}

		return;
	}
}
