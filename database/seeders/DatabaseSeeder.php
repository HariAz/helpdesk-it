<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SlaConfig;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketStatusLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users
        $supervisor = User::create([
            'name' => 'Ahmad Supervisor',
            'email' => 'supervisor@helpdesk.com',
            'password' => Hash::make('password123'),
            'role' => 'supervisor',
            'department' => 'IT Management',
            'is_active' => true,
        ]);

        $teknisi1 = User::create([
            'name' => 'Budi Teknisi',
            'email' => 'teknisi1@helpdesk.com',
            'password' => Hash::make('password123'),
            'role' => 'teknisi',
            'department' => 'IT Support',
            'is_active' => true,
        ]);

        $teknisi2 = User::create([
            'name' => 'Citra Teknisi',
            'email' => 'teknisi2@helpdesk.com',
            'password' => Hash::make('password123'),
            'role' => 'teknisi',
            'department' => 'IT Support',
            'is_active' => true,
        ]);

        $user1 = User::create([
            'name' => 'Deni User',
            'email' => 'user1@helpdesk.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'department' => 'Keuangan',
            'is_active' => true,
        ]);

        $user2 = User::create([
            'name' => 'Eka User',
            'email' => 'user2@helpdesk.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'department' => 'HRD',
            'is_active' => true,
        ]);

        // SLA Configs
        SlaConfig::insert([
            ['priority' => 'kritis', 'response_time_hours' => 1, 'resolution_time_hours' => 4],
            ['priority' => 'tinggi', 'response_time_hours' => 2, 'resolution_time_hours' => 8],
            ['priority' => 'sedang', 'response_time_hours' => 4, 'resolution_time_hours' => 24],
            ['priority' => 'rendah', 'response_time_hours' => 8, 'resolution_time_hours' => 72],
        ]);

        // Categories
        $categories = [
            ['name' => 'Hardware', 'icon' => 'pc-display', 'children' => ['PC & Laptop', 'Printer & Scanner', 'Peripheral']],
            ['name' => 'Software', 'icon' => 'app-indicator', 'children' => ['Instalasi Aplikasi', 'Error Aplikasi', 'Sistem Operasi']],
            ['name' => 'Akses & Akun', 'icon' => 'shield-lock', 'children' => ['Reset Password', 'Hak Akses', 'Email & Akun']],
            ['name' => 'Internet & Jaringan', 'icon' => 'wifi', 'children' => ['Koneksi Internet', 'VPN', 'Jaringan Lokal']],
            ['name' => 'Lainnya', 'icon' => 'three-dots', 'children' => ['Konsultasi IT', 'Permintaan Umum']],
        ];

        $catModels = [];
        foreach ($categories as $catData) {
            $cat = Category::create([
                'name' => $catData['name'],
                'slug' => Str::slug($catData['name']),
                'icon' => $catData['icon'],
                'is_active' => true,
                'sort_order' => 0,
            ]);
            $catModels[] = $cat;
            foreach ($catData['children'] as $i => $childName) {
                Category::create([
                    'name' => $childName,
                    'slug' => Str::slug($childName) . '-' . $cat->id,
                    'parent_id' => $cat->id,
                    'is_active' => true,
                    'sort_order' => $i,
                ]);
            }
        }

        // Sample Tickets
        $ticketData = [
            ['Printer di lantai 2 tidak bisa print', 'kritis', 'open', $user1, null, $catModels[0]],
            ['Reset password akun domain', 'sedang', 'assigned', $user1, $teknisi1, $catModels[2]],
            ['Laptop tidak bisa konek WiFi', 'tinggi', 'in_progress', $user2, $teknisi1, $catModels[3]],
            ['Instalasi Microsoft Office', 'rendah', 'resolved', $user2, $teknisi2, $catModels[1]],
            ['VPN tidak bisa terkoneksi dari rumah', 'tinggi', 'in_progress', $user1, $teknisi2, $catModels[3]],
            ['PC sangat lambat saat startup', 'sedang', 'pending_user', $user2, $teknisi1, $catModels[0]],
            ['Error saat membuka aplikasi SIMPEG', 'tinggi', 'open', $user1, null, $catModels[1]],
            ['Minta tambah RAM laptop', 'rendah', 'closed', $user2, $teknisi2, $catModels[0]],
            ['Internet di ruang rapat sering disconnect', 'sedang', 'assigned', $user1, $teknisi1, $catModels[3]],
            ['Aktivasi license antivirus baru', 'rendah', 'open', $user2, null, $catModels[1]],
        ];

        foreach ($ticketData as $i => [$title, $priority, $status, $reporter, $assignee, $category]) {
            $ticketNumber = 'TKT-' . now()->format('Ymd') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            $slaHours = match($priority) { 'kritis' => 4, 'tinggi' => 8, 'sedang' => 24, 'rendah' => 72 };
            $createdAt = now()->subHours(rand(1, 48));

            $ticket = Ticket::create([
                'ticket_number' => $ticketNumber,
                'title' => $title,
                'description' => "Deskripsi detail masalah: $title. Mohon segera ditangani sesuai prioritas yang ditetapkan.",
                'user_id' => $reporter->id,
                'assigned_to' => $assignee?->id,
                'category_id' => $category->id,
                'priority' => $priority,
                'status' => $status,
                'sla_deadline' => $createdAt->copy()->addHours($slaHours),
                'is_escalated' => in_array($priority, ['kritis', 'tinggi']) && rand(0, 1),
                'resolved_at' => $status === 'resolved' || $status === 'closed' ? now()->subHours(rand(1, 5)) : null,
                'closed_at' => $status === 'closed' ? now()->subHours(rand(1, 2)) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            TicketStatusLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $reporter->id,
                'from_status' => null,
                'to_status' => 'open',
                'note' => 'Tiket dibuat',
                'created_at' => $createdAt,
            ]);

            if ($status !== 'open' && $assignee) {
                TicketStatusLog::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $supervisor->id,
                    'from_status' => 'open',
                    'to_status' => 'assigned',
                    'note' => 'Tiket di-assign ke teknisi',
                    'created_at' => $createdAt->copy()->addMinutes(30),
                ]);
            }
        }
    }
}
