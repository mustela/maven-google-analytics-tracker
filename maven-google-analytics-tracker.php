<?php


/*
  Plugin Name: Maven Google Analytics Tracker
  Plugin URI:
  Description:
  Author: Site Mavens
  Version: 0.1
  Author URI:
 */

namespace MavenGoogleAnalyticsTracker;


// Exit if accessed directly 
if ( ! defined( 'ABSPATH' ) ) exit;

use Maven\Settings\OptionType,
	Maven\Settings\Option;


class Tracker extends \Maven\Tracking\BaseTracker {
	
	public function __construct( $args = array() ) {
		parent::__construct('GoogleAnalytics');
		
		$domain = "";
		if ( function_exists( 'home_url' ) ) {
			$domain = home_url();
		}

		$analyticsAccountId = "";
		if ( $args && isset( $args[ 'analyticsAccountId' ] ) ) {
			$analyticsAccountId = $args[ 'analyticsAccountId' ];
		}
		
		
		$defaultOptions = array(
			new Option(
					"analyticsAccountId",
					"Analytics Account Id",
					$analyticsAccountId,
					'',
					OptionType::Input
			),
			new Option(
					"domain",
					"Doman",
					$domain,
					'',
					OptionType::Input
			)
		);

		$this->addSettings( $defaultOptions );
	}
	
	public function addTransaction ( \Maven\Tracking\EcommerceTransaction $transaction ){
		
		if ( !$transaction || !$transaction->getOrderId() || !$transaction->getTotal() ) {
			return;
		}
		
		\Maven\Loggers\Logger::log()->message( 'MavenGoogleAnalyticsTracker/Tracker/addTransaction: '.$this->getSetting( 'analyticsAccountId' ));

		//WE need to load the library
		\Maven\Core\Loader::load(__DIR__, '/autoload.php');
		
		$tracker = new \UnitedPrototype\GoogleAnalytics\Tracker($this->getSetting( 'analyticsAccountId' ),$this->getSetting( 'domain' ) );
		
		$session = new \UnitedPrototype\GoogleAnalytics\Session();
		
		$visitor = new \UnitedPrototype\GoogleAnalytics\Visitor();
		
		$request = \Maven\Core\Request::current();
		
		$visitor->setIpAddress($request->getIp());
		$visitor->setUserAgent($request->getUserAgent());
		
		$gaTransaction = new \UnitedPrototype\GoogleAnalytics\Transaction();
		$gaTransaction->setOrderId( $transaction->getOrderId() );
		$gaTransaction->setTotal	( $transaction->getTotal() );
		$gaTransaction->setShipping( $transaction->getShipping() );
		$gaTransaction->setTax( $transaction->getTaxes());
		
		$items = $transaction->getItems();
		
		foreach ( $items as $item ){
			
			$gaItem = new \UnitedPrototype\GoogleAnalytics\Item();
			$gaItem->setOrderId	( $item->getOrderId() );
			$gaItem->setName		( $item->getName());
			$gaItem->setQuantity	( $item->getQuantity() );
			$gaItem->setPrice		( $item->getPrice() );
			$gaItem->setSku			( $item->getSku() );
			
			$gaTransaction->addItem( $gaItem );

		}
		
		
		$tracker->trackTransaction($gaTransaction, $session, $visitor);


	}
	
	
	public function addEvent ( \Maven\Tracking\Event $event ){
		
		return false;
	}
}
 
$tracker = new Tracker();
\Maven\Core\HookManager::instance()->addFilter('maven/trackers/register', array($tracker,'register'));