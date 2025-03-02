<?php

namespace Elgg\Router\Middleware;

use Elgg\Exceptions\Http\EntityPermissionsException;
use Elgg\IntegrationTestCase;

class PageOwnerCanEditGatekeeperIntegrationTest extends IntegrationTestCase {

	/**
	 * @var \ElggUser test user
	 */
	protected $user;
	
	/**
	 * @var \ElggUser test user
	 */
	protected $loggedin_user;
	
	/**
	 * @var \ElggGroup test group
	 */
	protected $group;
	
	/**
	 * @var \ElggObject test object, owned by test user
	 */
	protected $object;
	
	/**
	 * @dataProvider routeUserDataProvider
	 */
	public function testLoggedOutCantAccessUserPage(string $route_name, array $route_params) {
		
		$this->user = $this->createUser();
		
		elgg_register_route($route_name, $route_params);
		$url = elgg_generate_url($route_name, [
			'username' => $this->user->username,
			'guid' => $this->user->guid,
		]);
		
		$http_request = $this->prepareHttpRequest($url);
		
		_elgg_services()->setValue('request', $http_request);
		
		$this->expectException(EntityPermissionsException::class);
		_elgg_services()->router->route($http_request);
	}
	
	/**
	 * @dataProvider routeGroupDataProvider
	 */
	public function testLoggedOutCantAccessGroupPage(string $route_name, array $route_params) {
		
		$this->user = $this->createUser();
		$this->group = $this->createGroup([
			'owner_guid' => $this->user->guid,
			'container_guid' => $this->user->guid,
		]);
		
		elgg_register_route($route_name, $route_params);
		$url = elgg_generate_url($route_name, [
			'guid' => $this->group->guid,
		]);
		
		$http_request = $this->prepareHttpRequest($url);
		
		_elgg_services()->setValue('request', $http_request);
		
		$this->expectException(EntityPermissionsException::class);
		_elgg_services()->router->route($http_request);
	}
	
	/**
	 * @dataProvider routeEntityDataProvider
	 */
	public function testLoggedOutCantAccessEntityPage(string $route_name, array $route_params) {
		
		$this->user = $this->createUser();
		$this->group = $this->createGroup([
			'owner_guid' => $this->user->guid,
			'container_guid' => $this->user->guid,
		]);
		$this->object = $this->createObject([
			'owner_guid' => $this->user->guid,
			'container_guid' => $this->group->guid,
		]);
		
		elgg_register_route($route_name, $route_params);
		$url = elgg_generate_url($route_name, [
			'guid' => $this->object->guid,
		]);
		
		$http_request = $this->prepareHttpRequest($url);
		
		_elgg_services()->setValue('request', $http_request);
		
		$this->expectException(EntityPermissionsException::class);
		_elgg_services()->router->route($http_request);
	}
	
	/**
	 * @dataProvider routeUserDataProvider
	 */
	public function testLoggedInOtherCantAccessUserPage(string $route_name, array $route_params) {
		
		$this->loggedin_user = $this->createUser();
		elgg_get_session()->setLoggedInUser($this->loggedin_user);
		
		$this->user = $this->createUser();
		
		elgg_register_route($route_name, $route_params);
		$url = elgg_generate_url($route_name, [
			'username' => $this->user->username,
			'guid' => $this->user->guid,
		]);
		
		$http_request = $this->prepareHttpRequest($url);
		
		_elgg_services()->setValue('request', $http_request);
		
		$this->expectException(EntityPermissionsException::class);
		_elgg_services()->router->route($http_request);
	}
	
	/**
	 * @dataProvider routeGroupDataProvider
	 */
	public function testLoggedInOtherCantAccessGroupPage(string $route_name, array $route_params) {
		
		$this->loggedin_user = $this->createUser();
		elgg_get_session()->setLoggedInUser($this->loggedin_user);
		
		$this->user = $this->createUser();
		$this->group = $this->createGroup([
			'owner_guid' => $this->user->guid,
			'container_guid' => $this->user->guid,
		]);
		
		elgg_register_route($route_name, $route_params);
		$url = elgg_generate_url($route_name, [
			'guid' => $this->group->guid,
		]);
		
		$http_request = $this->prepareHttpRequest($url);
		
		_elgg_services()->setValue('request', $http_request);
		
		$this->expectException(EntityPermissionsException::class);
		_elgg_services()->router->route($http_request);
	}
	
	/**
	 * @dataProvider routeEntityDataProvider
	 */
	public function testLoggedInOtherCantAccessEntityPage(string $route_name, array $route_params) {
		
		$this->loggedin_user = $this->createUser();
		elgg_get_session()->setLoggedInUser($this->loggedin_user);
		
		$this->user = $this->createUser();
		$this->group = $this->createGroup([
			'owner_guid' => $this->user->guid,
			'container_guid' => $this->user->guid,
		]);
		$this->object = $this->createObject([
			'owner_guid' => $this->user->guid,
			'container_guid' => $this->group->guid,
		]);
		
		elgg_register_route($route_name, $route_params);
		$url = elgg_generate_url($route_name, [
			'guid' => $this->object->guid,
		]);
		
		$http_request = $this->prepareHttpRequest($url);
		
		_elgg_services()->setValue('request', $http_request);
		
		$this->expectException(EntityPermissionsException::class);
		_elgg_services()->router->route($http_request);
	}
	
	/**
	 * @dataProvider routeUserDataProvider
	 */
	public function testLoggedInCanAccessUserPage(string $route_name, array $route_params) {
		
		$this->loggedin_user = $this->createUser();
		elgg_get_session()->setLoggedInUser($this->loggedin_user);
		
		elgg_register_route($route_name, $route_params);
		$url = elgg_generate_url($route_name, [
			'username' => $this->loggedin_user->username,
			'guid' => $this->loggedin_user->guid,
		]);
		
		$http_request = $this->prepareHttpRequest($url);
		
		_elgg_services()->setValue('request', $http_request);
		
		$response = _elgg_services()->router->route($http_request);
		$this->assertTrue($response);
	}
	
	/**
	 * @dataProvider routeGroupDataProvider
	 */
	public function testLoggedInCanAccessGroupPage(string $route_name, array $route_params) {
		
		$this->loggedin_user = $this->createUser();
		elgg_get_session()->setLoggedInUser($this->loggedin_user);
		
		$this->group = $this->createGroup([
			'owner_guid' => $this->loggedin_user->guid,
			'container_guid' => $this->loggedin_user->guid,
		]);
		
		elgg_register_route($route_name, $route_params);
		$url = elgg_generate_url($route_name, [
			'guid' => $this->group->guid,
		]);
		
		$http_request = $this->prepareHttpRequest($url);
		
		_elgg_services()->setValue('request', $http_request);
		
		$response = _elgg_services()->router->route($http_request);
		$this->assertTrue($response);
	}
	
	/**
	 * @dataProvider routeEntityDataProvider
	 */
	public function testLoggedInCanAccessEntityPage(string $route_name, array $route_params) {
		
		$this->loggedin_user = $this->createUser();
		elgg_get_session()->setLoggedInUser($this->loggedin_user);
		
		$this->group = $this->createGroup([
			'owner_guid' => $this->loggedin_user->guid,
			'container_guid' => $this->loggedin_user->guid,
		]);
		$this->object = $this->createObject([
			'owner_guid' => $this->loggedin_user->guid,
			'container_guid' => $this->group->guid,
		]);
		
		elgg_register_route($route_name, $route_params);
		$url = elgg_generate_url($route_name, [
			'guid' => $this->object->guid,
		]);
		
		$http_request = $this->prepareHttpRequest($url);
		
		_elgg_services()->setValue('request', $http_request);
		
		$response = _elgg_services()->router->route($http_request);
		$this->assertTrue($response);
	}
	
	public function routeUserDataProvider() {
		return [
			[
				'collection:object:foo:owner',
				[
					'path' => '/foo/owner/{username}',
					'handler' => [$this, 'routeHandler'],
					'middleware' => [
						PageOwnerCanEditGatekeeper::class,
					],
					'walled' => false,
				],
			],
			[
				'add:object:foo',
				[
					'path' => '/foo/add/{guid}',
					'handler' => [$this, 'routeHandler'],
					'middleware' => [
						PageOwnerCanEditGatekeeper::class,
					],
					'walled' => false,
				],
			],
		];
	}
	
	public function routeGroupDataProvider() {
		return [
			[
				'collection:object:foo:group',
				[
					'path' => '/foo/group/{guid}',
					'handler' => [$this, 'routeHandler'],
					'middleware' => [
						PageOwnerCanEditGatekeeper::class,
					],
					'walled' => false,
				],
			],
			[
				'add:object:foo',
				[
					'path' => '/foo/add/{guid}',
					'handler' => [$this, 'routeHandler'],
					'middleware' => [
						PageOwnerCanEditGatekeeper::class,
					],
					'walled' => false,
				],
			],
		];
	}
	
	public function routeEntityDataProvider() {
		return [
			[
				'view:object:foo',
				[
					'path' => '/foo/view/{guid}',
					'handler' => [$this, 'routeHandler'],
					'middleware' => [
						PageOwnerCanEditGatekeeper::class,
					],
					'walled' => false,
				],
			],
			[
				'edit:object:foo',
				[
					'path' => '/foo/edit/{guid}',
					'handler' => [$this, 'routeHandler'],
					'middleware' => [
						PageOwnerCanEditGatekeeper::class,
					],
					'walled' => false,
				],
			],
		];
	}
	
	public function routeHandler(array $page_segments = []) {
		return true;
	}
}
