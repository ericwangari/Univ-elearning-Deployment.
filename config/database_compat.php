<?php

class AppPDOStatement {
    private $statement;
    private $driver;

    public function __construct($statement, $driver) {
        $this->statement = $statement;
        $this->driver = $driver;
    }

    public function execute($params = null) {
        return $params === null ? $this->statement->execute() : $this->statement->execute($params);
    }

    public function fetch($mode = null, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0) {
        $row = $mode === null ? $this->statement->fetch() : $this->statement->fetch($mode, $cursorOrientation, $cursorOffset);
        return $this->mapRow($row);
    }

    public function fetchAll($mode = null, ...$args) {
        $rows = $mode === null ? $this->statement->fetchAll() : $this->statement->fetchAll($mode, ...$args);
        if (!is_array($rows) || $this->driver !== 'pgsql') return $rows;
        foreach ($rows as $key => $row) $rows[$key] = $this->mapRow($row);
        return $rows;
    }

    public function fetchColumn($column = 0) { return $this->statement->fetchColumn($column); }
    public function rowCount() { return $this->statement->rowCount(); }
    public function __call($method, $args) { return $this->statement->$method(...$args); }

    private function mapRow($row) {
        if (!is_array($row) || $this->driver !== 'pgsql') return $row;
        $map = [
            'userid'=>'UserID','username'=>'Username','email'=>'Email','password'=>'Password','usertype'=>'UserType','status'=>'Status',
            'emailverifiedat'=>'EmailVerifiedAt','createdat'=>'CreatedAt','lastactiveat'=>'LastActiveAt','tokenid'=>'TokenID','tokenhash'=>'TokenHash',
            'expiresat'=>'ExpiresAt','usedat'=>'UsedAt','courseid'=>'CourseID','coursename'=>'CourseName','description'=>'Description','enrollmentid'=>'EnrollmentID',
            'enrollmentdate'=>'EnrollmentDate','completionstatus'=>'CompletionStatus','contentid'=>'ContentID','contenttype'=>'ContentType','contenttitle'=>'ContentTitle',
            'contenturl'=>'ContentURL','createdby'=>'CreatedBy','updatedat'=>'UpdatedAt','quizid'=>'QuizID','quizname'=>'QuizName','quiztype'=>'QuizType',
            'totalmarks'=>'TotalMarks','resultid'=>'ResultID','score'=>'Score','submittedat'=>'SubmittedAt','questionid'=>'QuestionID','questiontext'=>'QuestionText',
            'questiontype'=>'QuestionType','marks'=>'Marks','optionid'=>'OptionID','optiontext'=>'OptionText','iscorrect'=>'IsCorrect','answerid'=>'AnswerID',
            'selectedoptionid'=>'SelectedOptionID','answertext'=>'AnswerText','attemptid'=>'AttemptID','startedat'=>'StartedAt','instructorcourseid'=>'InstructorCourseID',
            'instructorid'=>'InstructorID','assignedat'=>'AssignedAt','progressid'=>'ProgressID','completedlessons'=>'CompletedLessons','totallessons'=>'TotalLessons',
            'lastaccessedat'=>'LastAccessedAt','messageid'=>'MessageID','senderid'=>'SenderID','receiverid'=>'ReceiverID','messagetext'=>'MessageText',
            'content'=>'Content','sentat'=>'SentAt','isread'=>'IsRead','totalquizzes'=>'TotalQuizzes','completedquizzes'=>'CompletedQuizzes',
            'studentcount'=>'StudentCount','attemptcount'=>'AttemptCount'
        ];
        foreach ($row as $key => $value) {
            if (is_string($key)) {
                $lower = strtolower($key);
                if (isset($map[$lower]) && !array_key_exists($map[$lower], $row)) $row[$map[$lower]] = $value;
            }
        }
        return $row;
    }
}

class AppPDO extends PDO {
    private $driverName;

    public function __construct($dsn, $username, $password, $options, $driverName) {
        $this->driverName = $driverName;
        parent::__construct($dsn, $username, $password, $options);
    }

    #[\ReturnTypeWillChange]
    public function prepare($statement, $options = []) {
        $prepared = parent::prepare($this->adaptSql($statement), $options);
        return $prepared ? new AppPDOStatement($prepared, $this->driverName) : false;
    }

    #[\ReturnTypeWillChange]
    public function query($query, ?int $fetchMode = null, ...$fetchModeArgs) {
        $adapted = $this->adaptSql($query);
        $statement = $fetchMode === null ? parent::query($adapted) : parent::query($adapted, $fetchMode, ...$fetchModeArgs);
        return $statement ? new AppPDOStatement($statement, $this->driverName) : false;
    }

    #[\ReturnTypeWillChange]
    public function exec($statement) { return parent::exec($this->adaptSql($statement)); }

    private function adaptSql($sql) {
        if ($this->driverName !== 'pgsql') return $sql;

        $hadInsertIgnore = preg_match('/\bINSERT\s+IGNORE\s+INTO\b/i', $sql) === 1;
        $sql = str_replace('`', '', $sql);
        $sql = preg_replace('/\bINSERT\s+IGNORE\s+INTO\b/i', 'INSERT INTO', $sql);
        $sql = preg_replace('/SHOW\s+TABLES\s+LIKE\s+\?/i', "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = lower(?)", $sql);
        $sql = preg_replace('/SHOW\s+COLUMNS\s+FROM\s+([A-Za-z_][A-Za-z0-9_]*)\s+LIKE\s+\?/i', "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '$1' AND column_name = lower(?)", $sql);
        $sql = preg_replace('/\bIsRead\s*=\s*0\b/i', 'IsRead = FALSE', $sql);
        $sql = preg_replace('/\bIsRead\s*=\s*1\b/i', 'IsRead = TRUE', $sql);
        $sql = preg_replace('/(INSERT\s+INTO\s+messages\s*\([^)]*IsRead[^)]*\)\s*VALUES\s*\(\s*\?\s*,\s*\?\s*,\s*\?\s*,\s*)0(\s*\))/is', '$1FALSE$2', $sql);
        $sql = preg_replace('/DELETE\s+ua\s+FROM\s+user_answers\s+ua\s+JOIN\s+questions\s+q\s+ON\s+ua\.QuestionID\s*=\s*q\.QuestionID\s+WHERE\s+ua\.UserID\s*=\s*\?\s+AND\s+q\.QuizID\s*=\s*\?/is', 'DELETE FROM user_answers ua USING questions q WHERE ua.QuestionID = q.QuestionID AND ua.UserID = ? AND q.QuizID = ?', $sql);

        $upsert = 'ON CONFLICT (UserID, QuestionID) DO UPDATE SET ';
        $sql = preg_replace('/ON\s+DUPLICATE\s+KEY\s+UPDATE\s+AnswerText\s*=\s*\?,\s*IsCorrect\s*=\s*NULL/i', $upsert . 'AnswerText = EXCLUDED.AnswerText, IsCorrect = NULL', $sql);
        $sql = preg_replace('/ON\s+DUPLICATE\s+KEY\s+UPDATE\s+SelectedOptionID\s*=\s*\?,\s*AnswerText\s*=\s*\?,\s*IsCorrect\s*=\s*\?/i', $upsert . 'SelectedOptionID = EXCLUDED.SelectedOptionID, AnswerText = EXCLUDED.AnswerText, IsCorrect = EXCLUDED.IsCorrect', $sql);
        $sql = preg_replace('/ON\s+DUPLICATE\s+KEY\s+UPDATE\s+SelectedOptionID\s*=\s*\?,\s*IsCorrect\s*=\s*\?/i', $upsert . 'SelectedOptionID = EXCLUDED.SelectedOptionID, IsCorrect = EXCLUDED.IsCorrect', $sql);

        if ($hadInsertIgnore && stripos($sql, 'ON CONFLICT') === false) {
            $sql = rtrim(rtrim($sql), ';') . ' ON CONFLICT DO NOTHING';
        }

        return $sql;
    }
}
