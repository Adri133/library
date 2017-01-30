<?php

use Gregwar\Image\Image;

$app->match('/', function() use ($app) {
    return $app['twig']->render('home.html.twig');
})->bind('home');

$app->match('/books', function() use ($app) {
    return $app['twig']->render('books.html.twig', array(
        'books' => $app['model']->getBooks()
    ));
})->bind('books');

$app->match('/cardBooks', function() use ($app) {
  //Question 3
    $request = $app['request'];
    $bookid = $_GET["bookId"];
    $bookSame = $app['model']->getBookSame($bookid);
    $count = 0;
    foreach ($bookSame as $key => $value) {
      if ($value['dispo'] == '1' ) {
        $count++;
      }
    }

    return $app['twig']->render('cardBooks.html.twig', array(
     'bookSame' => $bookSame, 'count' => $count
   ));
})->bind('cardBooks');
  //Question 6
$app->match('/loanForm', function() use ($app) {
  $bookId = $_GET["book"]["book_id"];
  $request = $app['request'];
  $success = false;
  $loanDate = (new \DateTime())->setTimeZone(new DateTimeZone('Europe/Berlin'));
  $loanDateDisplay = $loanDate->format("Y-m-d H:i:s");;
  var_dump($loanDateDisplay);

  if ($request->getMethod() == 'POST') {
      $post = $request->request;
      if ($post->has('name') && $post->has('returnDate')) {
          $isReturn = 0;
          $arrayIsInDb = $app['model']->isInDb($bookId);
          if (sizeof($arrayIsInDb) > 0) {
            $app['model']->updateLoan($post->get('name'), $loanDateDisplay,
            $post->get('returnDate'), $isReturn, $bookId);
              $success = true;
          }
            else {
            $app['model']->insertLoan($post->get('name'), (int)$bookId, $loanDateDisplay,
            $post->get('returnDate'), $isReturn);
            $success = true;
          }
         }
    }
  return $app['twig']->render('loanForm.html.twig', array(
   'success' => $success,
   'loanDateDisplay' => $loanDateDisplay
 ));
})->bind('loanForm');

$app->match('/admin', function() use ($app) {
    $request = $app['request'];
    $success = false;
    if ($request->getMethod() == 'POST') {
        $post = $request->request;
        //Question 2
        $admin1 = $app['config']['admin']['admin1'];
        $admin2 = $app['config']['admin']['admin2'];
        $admin3 = $app['config']['admin']['admin3'];
        if ($post->has('login') && $post->has('password') &&
            array($post->get('login'), $post->get('password')) == ($admin1 || $admin2 || $admin3)) {
            $app['session']->set('admin', true);
                $success = true;

    }
  }
    return $app['twig']->render('admin.html.twig', array(
      'success' => $success
    ));

})->bind('admin');

$app->match('/logout', function() use ($app) {
    $app['session']->remove('admin');
    return $app->redirect($app['url_generator']->generate('admin'));
})->bind('logout');

$app->match('/addBook', function() use ($app) {
    if (!$app['session']->has('admin')) {
        return $app['twig']->render('shouldBeAdmin.html.twig');
    }

    $request = $app['request'];
    if ($request->getMethod() == 'POST') {
        $post = $request->request;
        if ($post->has('title') && $post->has('author') && $post->has('synopsis') &&
            $post->has('copies')) {
            $files = $request->files;
            $image = '';

            // Resizing image
            if ($files->has('image') && $files->get('image')) {
                $image = sha1(mt_rand().time());
                Image::open($files->get('image')->getPathName())
                    ->resize(240, 300)
                    ->save('uploads/'.$image.'.jpg');
                Image::open($files->get('image')->getPathName())
                    ->resize(120, 150)
                    ->save('uploads/'.$image.'_small.jpg');
            }

            // Saving the book to database
            $app['model']->insertBook($post->get('title'), $post->get('author'), $post->get('synopsis'),
                $image, (int)$post->get('copies'));
        }
    }

    return $app['twig']->render('addBook.html.twig');
})->bind('addBook');
