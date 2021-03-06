<?php
namespace spec\rtens\dox;

use rtens\dox\Executer;
use rtens\mockster\Mock;
use rtens\mockster\MockFactory;
use spec\rtens\dox\fixtures\FileFixture;
use spec\rtens\dox\fixtures\WebFixture;
use watoki\scrut\Specification;

/**
 * Projects can be checked-out and updated automatically
 *
 * @property WebFixture web <-
 * @property FileFixture file <-
 * @property Mock executer
 */
class VcsIntegrationTest extends Specification {

    /**
     * Projects can be updated using a [web hook](https://help.github.com/articles/creating-webhooks)
     */
    public function testUpdatingByWebHook() {
        $this->web->givenTheProject('Project');
        $this->file->givenTheFolder('user/projects/Project/.git');

        $this->web->whenISendA_RequestTo('post', 'projects/Project');

        $this->thenExecutedCommand_ShouldBe(1, 'cd [root]/user/projects/Project && git pull origin master');
        $this->web->thenTheResponseShouldBe('OK - Updated Project');
    }

    public function testDoNotUpdateIfNotUnderGit() {
        $this->web->givenTheProject('Project');
        $this->file->givenTheFolder('user/projects/Project');

        $this->web->whenITryToSendA_RequestTo('post', 'projects/Project');

        $this->web->thenAnExceptionShouldBeThrownContaining('Not a git repository');
    }

    /**
     * If the project files don't exist yet, they are cloned
     */
    public function testCloneOnDemand() {
        $this->web->givenTheProject('MyProject');
        $this->web->givenTheProject_HasTheRepositoryUrl('MyProject', 'some/repo.git');

        $this->web->whenIRequestTheResourceAt('projects/MyProject');

        $this->thenExecutedCommand_ShouldBe(1, 'mkdir [root]/user/projects/MyProject');
        $this->thenExecutedCommand_ShouldBe(2, 'cd [root]/user/projects/MyProject && git clone some/repo.git .');
    }

    public function testCloneFailsIfNoRepositoryIsSet() {
        $this->web->givenTheProject('MyProject');
        $this->web->whenITryToSendA_RequestTo('get', 'projects/MyProject');
        $this->web->thenAnExceptionShouldBeThrownContaining('Cannot clone [MyProject]. No repository set.');
    }

    public function testDoNotCloneIfFolderExists() {
        $this->web->givenTheProject('MyProject');
        $this->web->givenTheProject_HasTheRepositoryUrl('MyProject', 'some/repo.git');
        $this->file->givenTheFolder('user/projects/MyProject');

        $this->web->whenITryToSendA_RequestTo('get', 'projects/MyProject');

        $this->web->thenAnExceptionShouldBeThrownContaining('Project folder already exists');
    }

    protected function setUp() {
        parent::setUp();
        $mf = new MockFactory();
        $this->executer = $mf->getMock(Executer::$CLASS);
        $this->factory->setSingleton(Executer::$CLASS, $this->executer);
    }

    private function thenExecutedCommand_ShouldBe($pos, $command) {
        $command = str_replace('[root]', $this->file->tmpDir(), $command);
        $command = str_replace('\\', '/', $command);
        $history = $this->executer->__mock()->method('execute')->getHistory();
        $this->assertGreaterThanOrEqual($pos, $history->getCalledCount());
        $this->assertEquals($command, str_replace('\\', '/', $history->getCalledArgumentAt($pos - 1, 0)));
    }

}