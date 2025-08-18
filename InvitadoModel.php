<?php
class InvitadoModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $stmt = $this->pdo->query('SELECT * FROM invitados');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add($data) {
        $sql = 'INSERT INTO invitados (attendance, fridayAttendance, lastName, name, phone, songSuggestion) VALUES (?, ?, ?, ?, ?, ?)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['attendance'],
            $data['fridayAttendance'],
            $data['lastName'],
            $data['name'],
            $data['phone'],
            $data['songSuggestion']
        ]);
        return $this->pdo->lastInsertId();
    }
}
