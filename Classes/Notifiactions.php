<?php
class Notifiactions
{
    public static function showNotification($id)
    {
        $db = Database::getConnection();
        if ($db === null) {
            throw new Exception("Database connection failed");
        }

        $query = "SELECT u.*,n.*,n.created_at as notTime from notifications n
        inner join users u on u.id = n.user_id
        where u.id =?
       ";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifiactions = [];
        while ($data = $result->fetch_assoc()) {
            $notifiactions[] = $data;
        }
        $stmt->close();
        return $notifiactions;

    }
}