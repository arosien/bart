<?php
$path = dirname(dirname(__DIR__)) . '/';
require_once $path . 'setup.php';

class Git_Hook_Pre_Receive_Runner_Test extends Bart_Base_Test_Case
{
	public function test_conf_key_missing()
	{
		$repo = 'Isengard';
		$hook_conf = array(
			'pre-receive' => array('names' => 'jenkins'),
		);

		$dipr = $this->configure_for($hook_conf, $repo);
		$pre_receive = $dipr['pr'];

		$msg = 'No configuration section for hook jenkins';
		$closure = function() use ($pre_receive) {
			$pre_receive->verify_all('doesnt matter');
		};
		$this->assert_throws('Exception', $msg, $closure);
	}

	public function test_no_class_exists()
	{
		$repo = 'Isengard';
		$monty = 'sit_not_appearing_in_this_film';
		$hook_conf = array(
			'pre-receive' => array('names' => 'jenkins'),
			'jenkins' => array('class' => $monty),
		);

		$dipr = $this->configure_for($hook_conf, $repo);
		$pre_receive = $dipr['pr'];

		$msg = "Class for hook does not exist! (Git_Hook_$monty)";
		$closure = function() use ($pre_receive) {
			$pre_receive->verify_all('doesnt matter');
		};
		$this->assert_throws('Exception', $msg, $closure);
	}

	public function test_disabled_class()
	{
		$repo = 'Isengard';
		$hook_conf = array(
			'pre-receive' => array('names' => 'jenkins'),
			'jenkins' => array(
				'class' => 'Gerrit_Approved',
				'enabled' => false,
			),
		);

		$dipr = $this->configure_for($hook_conf, $repo);

		$pre_receive = $dipr['pr'];
		// Not necessarily accurate, but it should be true that if pre-receive
		// ...attempted to instantiate the Git_Hook it would crash when it
		// ...it couldn't find the dependency for class Git_Hook_Gerrit_Approved
		$pre_receive->verify_all('doesnt matter');
	}

	public function test_verify_fails()
	{
		$repo = 'Isengard';
		$hook_conf = array(
			'pre-receive' => array('names' => 'jenkins'),
			'jenkins' => array(
				'class' => 'For_Testing',
				'verbose' => false,
				'enabled' => true,
				'super' => 'duper',
			),
		);

		$dipr = $this->configure_for($hook_conf, $repo);

		$di = $dipr['di'];
		$phpu = $this;
		$di->register_local('Git_Hook_For_Testing', 'phpu', function() use ($phpu){
			return $phpu;
		});

		$pre_receive = $dipr['pr'];
		$pre_receive->verify_all('doesnt matter');
	}

	private function configure_for($hook_conf, $repo)
	{
		$git_dir = '.git';
		$w = new Witness_Silent();

		$mock_conf = $this->getMock('Config_Parser', array(), array(), '', false);
		$mock_conf->expects($this->once())
				->method('parse_conf_file')
				->with($this->equalTo(BART_DIR . 'etc/php/hooks.conf'))
				->will($this->returnValue($hook_conf));

		$phpu = $this;
		$create_conf = function($params) use ($phpu, $mock_conf, $repo) {
			$phpu->assertEquals($repo, $params['repo'],
					'Repo not passed to Config_Parser constructor');

			return $mock_conf;
		};
		$di = new Diesel();
		$di->register_local('Git_Hook_Pre_Receive_Runner', 'Config_Parser', $create_conf);

		$pre_receive = new Git_Hook_Pre_Receive_Runner($git_dir, $repo, $w, $di);

		return array('di' => $di, 'pr' => $pre_receive);
	}
}

class Git_Hook_For_Testing extends Git_Hook_Base
{
	protected $conf;
	protected $dir;
	protected $repo;
	protected $di;

	public function __construct(array $conf, $dir, $repo, Witness $w, Diesel $di)
	{
		$this->conf = $conf;
		$this->dir = $dir;
		$this->repo = $repo;
		$this->di = $di;
	}

	public function verify($commit_hash)
	{
		// Make sure everything got passed through as expected
		$phpu = $this->di->create($this, 'phpu');
		$phpu->assertEquals('Isengard', $this->repo, 'Wrong repo passed');
		$phpu->assertEquals('.git', $this->dir, 'Wrong git dir passed');
		$phpu->assertEquals('duper', $this->conf['jenkins']['super'], 'Wrong conf passed');
	}
}
