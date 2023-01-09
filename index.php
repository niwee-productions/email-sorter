<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Single page mail sorter - Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/css/bootstrap.min.css" integrity="sha512-SbiR/eusphKoMVVXysTKG/7VseWii+Y3FdHrt0EpKgpToZeemhqHeZeLWLhJutz/2ut2Vw1uQEj2MbRF+TVBUA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <section class="container">
        <?php

        // show debug
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        require_once 'vendor/autoload.php';

        echo "<h1>Single Page Mail Sorter</h1>";

        $env_file = __DIR__ . '/.env';
        if (file_exists($env_file))
        {
            $dotenv = new Symfony\Component\Dotenv\Dotenv();
            $dotenv->load($env_file);
        }


        $email = $_ENV['USER_EMAIL'];
        $password = $_ENV['USER_PASSWORD'];
        $imap_url = $_ENV['IMAP_URL'] . ":" . $_ENV['PORT'] . "/" . $_ENV['PROTOCOL'] . "/" . $_ENV['SECURE'];
        $folder = "INBOX";



        $mailbox = new PhpImap\Mailbox(
            '{' . $imap_url . '}' . $folder, // IMAP server and mailbox folder
            "$email", // Username for the before configured mailbox
            "$password", // Password for the before configured username
            __DIR__, // Directory, where attachments will be saved (optional)
            'UTF-8', // Server encoding (optional)
            true, // Trim leading/ending whitespaces of IMAP path (optional)
            false // Attachment filename mode (optional; false = random filename; true = original filename)
        );

        // set some connection arguments (if appropriate)
        $mailbox->setConnectionArgs(
            CL_EXPUNGE // expunge deleted mails upon mailbox close
        );

        if (!isset($_GET['deleteEmails']) && !isset($_GET['confirmDeleteEmails']) && !isset($_GET['search']))
        {
        ?>

            <div class="shadow rounded p-3">
                <h4>Email: <?php echo $email; ?></h4>
                <h6>IMAP server: <?php echo $imap_url; ?></h6>
                <p>Folder: <?php echo $folder; ?></p>
                <?php

                try
                {
                    // Get all emails (messages)
                    // PHP.net imap_search criteria: http://php.net/manual/en/function.imap-search.php
                    $mailsIds = $mailbox->searchMailbox('ALL');
                }
                catch (Exception $e)
                {
                    echo "IMAP connection failed: " . $e->getMessage();
                    die();
                }

                // If $mailsIds is empty, no emails could be found
                if (!$mailsIds)
                {
                    die("<div class='alert alert-danger mt-3' role='alert'>
                        No emails found in folder $folder
                    </div>");
                }
                else
                {
                ?>
                    <h6>Total number of emails: <?php echo count($mailsIds); ?> in folder <?php echo $folder; ?></h6>
                    <a href="index.php?viewAll" class="btn btn-primary">View All</a>
                <?php
                }
                ?>
            </div> <?php
                }
                else
                {
                    ?>
            <div class="shadow rounded p-3">
                <a href="index.php" class="btn btn-primary">Back</a>
            </div>
        <?php
                }

                // Search form
        ?>
        <div class="shadow rounded p-3 my-5">
            <h5>Search</h5>
            <form action="index.php?search" method="get">
                <input class="form-control mb-3" type="hidden" name="search" value="true">
                <input class="form-control mb-3" type="text" name="keywords" value="<?php echo (isset($_GET["keywords"]) ? $_GET['keywords'] : ""); ?>" placeholder="keywords">
                <select class="form-select mb-3" name="field" id="selectField">
                    <option <?php isset($_GET['field']) && $_GET['field'] === "FROM" && "selected"; ?> value="FROM">From</option>
                    <option <?php isset($_GET['field']) && $_GET['field'] === "TO" && "selected"; ?> value="TO">To</option>
                    <option <?php isset($_GET['field']) && $_GET['field'] === "SUBJECT" && "selected"; ?> value="SUBJECT">Subject</option>
                    <option <?php isset($_GET['field']) && $_GET['field'] === "BODY" && "selected"; ?> value="BODY">Body</option>
                </select>
                <input type="submit" value="Search" class="btn btn-primary">
            </form>
        </div>
        <?php if (isset($_GET['field'])) : ?>
            <script type="text/javascript">
                document.getElementById('selectField').value = "<?php echo $_GET['field']; ?>";
            </script>
        <?php endif; ?>
        <?php
        $search;

        if (isset($_GET['search']) && isset($_GET['keywords']) && isset($_GET['field']) && !isset($_GET['deleteEmails']))
        {
            $search = $_GET['search'];
            $field = $_GET['field'];
            $keywords = $_GET['keywords'];

            if (empty($search))
            {
                die('Search field is empty.');
            }
        ?>
            <?php

            // Search for a specific mail
            try
            {
                $search = $mailbox->searchMailbox("$field $keywords");
            }
            catch (Exception $e)
            {
                die("<div class='alert alert-danger mt-3' role='alert'>
                        " . $e->getMessage() . "
                    </div>");
            }
            if (!$search)
            {
                die("
                <div class='alert alert-danger mt-3' role='alert'>
                    No results found for '$keywords'
                </div>
                ");
            }

            ?>
            <div class="shadow rounded p-3 mt-3">
                <h3>Search results for '<?php echo $keywords; ?>': <?php echo count($search); ?></h3>
                <br>
                <br>
                <?php
                foreach ($search as $result)
                {
                ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title
                        <?php
                        $mail = $mailbox->getMail($result);
                        if ($mail->isUnseen)
                        {
                            echo "text-primary";
                        }
                        ?>
                        ">
                                <?php echo $mail->subject; ?>
                            </h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                <?php echo $mail->fromAddress; ?>
                            </h6>
                        </div>
                    </div>
                <?php
                }

                // Form to delete search results
                ?>
            </div>
            <form action="index.php?search=true&keywords=<?php echo $keywords; ?>&field=<?php echo $field; ?>&deleteEmails" method="get">
                <input type="hidden" name="search" value="true">
                <input type="hidden" name="keywords" value="<?php echo $keywords; ?>">
                <input type="hidden" name="field" value="<?php echo $field; ?>">
                <input type="hidden" name="deleteEmails" value="true">
                <input type="submit" value="Delete all search results" class="btn btn-danger my-3 w-100">
            </form>
            <?php
        }

        // Delete search results
        if (isset($_GET['deleteEmails']) && !isset($_GET['confirmDeleteEmails']))
        {
            try
            {
                if (empty($_GET['keywords']) || empty($_GET['field']))
                {
                    die('<div class="alert alert-danger mt-3" role="alert">
                            No search results to delete!
                        </div>');
                }

                $field = $_GET['field'];
                $keywords = $_GET['keywords'];
            ?>
                <div class="shadow rounded p-5 my-5">
                    <h5>Really delete these emails ?</h5>
                    <?php
                    $emails_to_delete = $mailbox->searchMailbox("$field $keywords");
                    foreach ($emails_to_delete as $email)
                    {
                    ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title
                        <?php
                        $mail = $mailbox->getMail($email);
                        if ($mail->isUnseen)
                        {
                            echo "text-primary";
                        }
                        ?>
                    
                        ">
                                    <?php echo $mail->subject; ?>
                                </h5>
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <?php echo $mail->fromAddress; ?>
                                </h6>
                            </div>
                        </div>
                    <?php
                    }


                    ?>
                    <form action="index.php?search=true&keywords=<?php echo $keywords; ?>&field=<?php echo $field; ?>&deleteEmails&confirmDeleteEmails" method="get">
                        <input type="hidden" name="search" value="true">
                        <input type="hidden" name="keywords" value="<?php echo $keywords; ?>">
                        <input type="hidden" name="field" value="<?php echo $field; ?>">
                        <input type="hidden" name="deleteEmails" value="true">
                        <input type="hidden" name="confirmDeleteEmails" value="true">
                        <input type="submit" value="Confirm" class="btn btn-danger my-3 w-100">
                    </form>
                </div>
            <?php
            }
            catch (Exception $e)
            {
                echo "IMAP connection failed: " . $e->getMessage();
                die();
            }
        }

        // Delete search results
        if (isset($_GET['deleteEmails']) && isset($_GET['confirmDeleteEmails']))
        {
            try
            {
                $field = $_GET['field'];
                $keywords = $_GET['keywords'];

                $emails_to_delete = $mailbox->searchMailbox("$field $keywords");
                foreach ($emails_to_delete as $email)
                {
                    $mailbox->deleteMail($email);
                }
            ?>
                <div class="alert alert-success" role="alert">
                    Search results deleted!
                </div>
            <?php
            }
            catch (Exception $e)
            {
                echo "IMAP connection failed: " . $e->getMessage();
                die();
            }
        }

        if (isset($_GET['viewAll']))
        {

            try
            {
                $allEmail = $mailbox->searchMailbox("ALL");
            }
            catch (Exception $e)
            {
                die("<div class='alert alert-danger mt-3' role='alert'>
                        " . $e->getMessage() . "
                    </div>");
            }
            if (!$allEmail)
            {
                die("
                <div class='alert alert-danger mt-3' role='alert'>
                    No emails found!
                </div>
                ");
            }

            ?>
            <div class="shadow rounded p-3 mt-3">
                <h3>All emails</h3>
                <?php
                $email_per_page = 10;
                $pagination = $_GET['pagination'] ?? 1;
                $i = 0;
                foreach ($allEmail as $result)
                {
                    if ($pagination == 1)
                    {
                        $start = 0;
                    }
                    else
                    {
                        $start = ($pagination - 1) * $email_per_page;
                    }


                    if ($start <= $i && $i <= $start + $email_per_page)
                    {
                ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title
                        <?php
                        $mail = $mailbox->getMail($result);
                        $date = $mail->date;
                        $formattedDate = date("\l\\e d/m/Y \à H:i", strtotime($date));
                        if ($mail->isUnseen)
                        {
                            echo "text-primary";
                        }
                        else
                        {
                            echo "text-secondary";
                        }
                        ?>
                        ">
                                    <?php echo $mail->subject; ?>
                                </h5>
                                <h6 class="card-subtitle mb-2 text-muted">
                                    de <?php echo $mail->fromAddress; ?>

                                    <?php echo $formattedDate; ?>
                                </h6>

                                <a href="index.php?deleteEmail=<?php echo $result; ?>" class="btn btn-danger">Delete</a>
                            </div>
                        </div>
                <?php
                    }
                    $i++;
                }

                // Form to delete search results
                ?>
                <nav aria-label="Page navigation example">
                    <ul class="pagination
                <?php
                if (count($allEmail) <= $email_per_page)
                {
                    echo "d-none";
                }
                ?>
                ">
                        <li class="page-item
                <?php
                if ($pagination == 1)
                {
                    echo "disabled";
                }
                ?>
                ">
                            <a class="page-link" href="index.php?viewAll&pagination=<?php echo $pagination - 1; ?>" tabindex="-1">Previous</a>
                        </li>
                        <li class="page-item
                <?php
                if ($pagination == ceil(count($allEmail) / $email_per_page))
                {
                    echo "disabled";
                }
                ?>
                ">
                            <a class="page-link" href="index.php?viewAll&pagination=<?php echo $pagination + 1; ?>">Next</a>
                        </li>
                    </ul>
                </nav>


            </div>
        <?php
        }


        ?>
    </section>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.5/cdn.js" integrity="sha512-1fAkW3wqng/WNu86nQEgW3/RuPns2JxdC6WwCFJhqB/fL9VIWduIJmktYGrlBu99aoxwmWKCLY4AHlzDsh6LqA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>

</html>