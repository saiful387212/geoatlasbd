<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\Db;

final class Category
{
    /** @return array<int, array{id:int,name:string,slug:string,description:?string,color:?string,icon:?string,sort_order:int}> */
    public static function allWithProjectCounts(): array
    {
        $sql = "SELECT c.id, c.name, c.slug, c.description, c.color, c.icon, c.sort_order,
                       COUNT(p.id) AS project_count
                  FROM categories c
             LEFT JOIN projects p
                    ON p.category_id = c.id
                   AND p.status = 'published'
              GROUP BY c.id, c.name, c.slug, c.description, c.color, c.icon, c.sort_order
              ORDER BY c.sort_order ASC, c.name ASC";
        $stmt = Db::pdo()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}