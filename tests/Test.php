<?php

use Dnoegel\DatabaseInspection\RouteProvider\DummyRouteProvider;
use Dnoegel\DatabaseInspection\Storage\InMemoryStorage;

class Test extends PHPUnit_Framework_TestCase
{

    /** @var  InMemoryStorage */
    private $storage;

    private $pdo;

    /**
     * @return \Dnoegel\DatabaseInspection\PDOInspectionDecorator
     */
    private function getPDO()
    {
        if ($this->pdo) {
            return $this->pdo;
        }

        $this->storage = new InMemoryStorage();

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $inspector = new \Dnoegel\DatabaseInspection\PDOInspectionDecorator($pdo);

        $inspector->setProblemInspector(new \Dnoegel\DatabaseInspection\SqlProblemInspector(
            $this->storage,
            new DummyRouteProvider()
        ));

        $this->pdo = $inspector;

        return $this->pdo;
    }

    public function testThis()
    {
        $sql = <<<EOF
CREATE TABLE test
(
id int,
name varchar(255)
);
EOF;

        $this->getPDO()->prepare($sql)->execute();

        $sql = 'INSERT INTO "test" (`name`) VALUES("Test")';
        $this->getPDO()->prepare($sql)->execute();
echo "<pre>";
print_r($this->storage->getDocument('problem', '42dec3f3d68a119b4faef11cd2b6afe3'));
exit();
        $this->assertEquals($sql, $this->storage->getDocument('problem', '42dec3f3d68a119b4faef11cd2b6afe3')['sql']);
        
    }
}