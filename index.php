<?php
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';
$settings = [
    'logger' => 0
];
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn = new mysqli($servername, $username, $password, $dbname);

$settings = ['logger' => 0];
$MadelineProto = new \danog\MadelineProto\API('session.madeline', $settings);
$MadelineProto->async(true);

function rawMem($MadelineProto) {
    $MadelineProto->loop(function () use ($MadelineProto) {
        yield $MadelineProto->start();
        $pwr_chat = yield $MadelineProto->getPwrChat('https://t.me/elemonvietnamgroup');
        $fp = fopen('elemon.csv', 'w');
        for ($i=0; $i < count($pwr_chat['participants']) - 1; $i++) {
            $user = $pwr_chat['participants'][$i]['user'];
            $fields = [$user['id'], $user['username'] ?? '', $user['first_name'] ?? '', $user['last_name'] ?? ''];
            fputcsv($fp, $fields);
        }
        fclose($fp);
    });
}

function addMemToGr($conn, $MadelineProto) {
    $MadelineProto->loop(function () use ($MadelineProto, $conn) {
        yield $MadelineProto->start();
        $sql = "SELECT id, tele_username, tele_id FROM users WHERE id >= 100 AND id < 1000";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                try {
                    // $result = yield $MadelineProto->channels->inviteToChannel(['channel' => 'https://t.me/PolyRocketOfficial', 'users' => [$row['tele_id']]]);
                    echo "- Done: ".$row['tele_username'].PHP_EOL;
                } catch (\Throwable $th) {
                    print_r($th);
                    continue;
                }
            }
        } else {
            echo "end";
        }
    });
    $conn->close();
}

function setId($conn, $MadelineProto) {
    $MadelineProto->loop(function () use ($MadelineProto, $conn) {
        $sql = "SELECT id, tele_username FROM users WHERE tele_id IS NULL AND id >= 100000 AND id < 120000";
        $result = $conn->query($sql);
        yield $MadelineProto->start();
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                try {
                    $user = yield $MadelineProto->getInfo($row['tele_username']);
                    if ($user['User'] && !$user['User']['bot']) {
                        $sql = "UPDATE users SET tele_id='{$user['User']['id']}' WHERE id={$row['id']}";
                        if ($conn->query($sql) === TRUE) {
                            echo $row['id']."-".$row['tele_username']."-".$user['User']['id'].PHP_EOL;
                        }
                    } else {
                        $sql = "UPDATE users SET tele_id='0' WHERE id={$row['id']}";
                        $conn->query($sql);
                        continue;
                    }
                } catch (\Throwable $th) {
                    $sql = "UPDATE users SET tele_id='0' WHERE id={$row['id']}";
                    $conn->query($sql);
                    continue;
                }
            }
        } else {
            echo "end";
        }
    });
    $conn->close();
}

function getMessage($MadelineProto)
{
    $MadelineProto->loop(function () use ($MadelineProto) {
        yield $MadelineProto->start();
        $channel = '@ByteNextVietnamCommunity';
        $limit = 100;
        $offset_id = 0;
        $time = "2021-11-10 21:00:00";
        $startTime = strtotime($time);
        $fp = fopen("{$time}.csv", 'w');
        do {
            $messages_Messages = yield $MadelineProto->messages->getHistory(['peer' => $channel, 'offset_id' => $offset_id, 'offset_date' => 0, 'add_offset' => 0, 'limit' => $limit, 'max_id' => 0, 'min_id' => 0, 'hash' => 0 ]);
            if (count($messages_Messages['messages']) == 0) break;
            foreach ($messages_Messages['messages'] as $message) {
                if (isset($message['message'])) {
                    echo "date: " . date('Y-m-d H:i:s', $message['date']) . " message: " . @$message['message'] . "\n";
                    $content = [date('Y-m-d H:i:s', $message['date']), $message['message']];
                    fputcsv($fp, $content);
                }
                if ($message['date'] < $startTime) return;
            }
            $offset_id = end($messages_Messages['messages'])['id'];
	        sleep(2);
        } while (true);
        fclose($fp);


    });
}
getMessage($MadelineProto);
// rawMem($MadelineProto);