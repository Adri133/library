<?php

class Model
{
    protected $pdo;

    public function __construct(array $config)
    {
        try {
            if ($config['engine'] == 'mysql') {
                $this->pdo = new \PDO(
                    'mysql:dbname='.$config['database'].';host='.$config['host'],
                    $config['user'],
                    $config['password']
                );
                $this->pdo->exec('SET CHARSET UTF8');
            } else {
                $this->pdo = new \PDO(
                    'sqlite:'.$config['file']
                );
            }
        } catch (\PDOException $error) {
            throw new ModelException('Unable to connect to database');
        }
    }

    /**
     * Tries to execute a statement, throw an explicit exception on failure
     */
    protected function execute(\PDOStatement $query, array $variables = array())
    {
        if (!$query->execute($variables)) {
            $errors = $query->errorInfo();
            throw new ModelException($errors[2]);
        }

        return $query;
    }

    /**
     * Inserting a book in the database
     */
    public function insertBook($title, $author, $synopsis, $image, $copies)
    {
        $query = $this->pdo->prepare('INSERT INTO livres (titre, auteur, synopsis, image)
            VALUES (?, ?, ?, ?)');
        $this->execute($query, array($title, $author, $synopsis, $image));


        $query = $this->pdo->prepare('INSERT INTO exemplaires (book_id)
            VALUES (?)');
        $this->execute($query, array($copies));
    }

    public function insertLoan($name, $bookId, $loanDate, $returnDate, $isReturn)
    {
        $query = $this->pdo->prepare('INSERT INTO emprunts (personne, exemplaire, debut, fin, fini)
            VALUES (?, ?, ?, ?, ?)');
        $this->execute($query, array($name, $bookId, $loanDate, $returnDate, $isReturn));
    }

    public function updateLoan($name, $loanDate, $returnDate, $isReturn, $bookId)
    {
        $query = $this->pdo->prepare("UPDATE emprunts SET emprunts.personne = '$name', emprunts.debut = '$loanDate', emprunts.fin = '$returnDate', emprunts.fini = '$isReturn' WHERE emprunts.exemplaire = $bookId");
        $this->execute($query, array($name, $loanDate, $returnDate, $isReturn, $bookId));
    }

    public function updateReturn($bookId) {
        $query = $this->pdo->prepare("UPDATE emprunts SET emprunts.fini = 1 WHERE emprunts.exemplaire = $bookId");
        $this->execute($query, array($bookId));
    }

    public function getBookSame($bookid)
    {
      $query = $this->pdo->prepare('SELECT livres.*, exemplaires.id as book_id, emprunts.fini as dispo FROM livres INNER JOIN exemplaires ON livres.id = exemplaires.book_id INNER JOIN emprunts ON exemplaires.id = emprunts.exemplaire where livres.id = '.$bookid.'');

      $this->execute($query);

      return $query->fetchAll();
      }

    public function isInDb($bookId)
    {
      $query = $this->pdo->prepare('SELECT emprunts.* FROM emprunts WHERE exemplaire = '.$bookId.'');

      $this->execute($query);

      return $query->fetchAll();
      }
  //
    /**
     * Getting all the books
     */
    public function getBooks()
    {
        $query = $this->pdo->prepare('SELECT livres.* FROM livres');

        $this->execute($query);

        return $query->fetchAll();
    }
}
