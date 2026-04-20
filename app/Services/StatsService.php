<?php

declare(strict_types=1);

namespace App\Services;

final class StatsService
{
    public function __construct(private readonly Database $db)
    {
    }

    public function record(string $sessionId, string $path): void
    {
        $this->db->execute(
            'INSERT INTO page_views (session_id, path, created_at) VALUES (:session_id, :path, :created_at)',
            [
                'session_id' => $sessionId,
                'path'       => $path,
                'created_at' => date('c'),
            ]
        );
    }

    public function summary(): array
    {
        $today = date('Y-m-d');
        $weekAgo = date('Y-m-d', strtotime('-6 days'));

        return [
            'views_today'            => (int) $this->db->fetchValue(
                "SELECT COUNT(*) FROM page_views WHERE date(created_at) = :day",
                ['day' => $today]
            ),
            'unique_sessions_today'  => (int) $this->db->fetchValue(
                "SELECT COUNT(DISTINCT session_id) FROM page_views WHERE date(created_at) = :day",
                ['day' => $today]
            ),
            'views_7days'            => (int) $this->db->fetchValue(
                "SELECT COUNT(*) FROM page_views WHERE date(created_at) >= :since",
                ['since' => $weekAgo]
            ),
            'unique_sessions_7days'  => (int) $this->db->fetchValue(
                "SELECT COUNT(DISTINCT session_id) FROM page_views WHERE date(created_at) >= :since",
                ['since' => $weekAgo]
            ),
            'views_total'            => (int) $this->db->fetchValue('SELECT COUNT(*) FROM page_views'),
            'unique_sessions_total'  => (int) $this->db->fetchValue('SELECT COUNT(DISTINCT session_id) FROM page_views'),
            'daily'                  => $this->dailyVisits(7),
        ];
    }

    private function dailyVisits(int $days): array
    {
        $since = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));

        $rows = $this->db->fetchAll(
            "SELECT date(created_at) AS day,
                    COUNT(*) AS views,
                    COUNT(DISTINCT session_id) AS unique_sessions
             FROM page_views
             WHERE date(created_at) >= :since
             GROUP BY day
             ORDER BY day ASC",
            ['since' => $since]
        );

        // Fill missing days with zeros so the chart is always complete
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['day']] = $row;
        }

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $result[] = $indexed[$day] ?? ['day' => $day, 'views' => 0, 'unique_sessions' => 0];
        }

        return $result;
    }
}
