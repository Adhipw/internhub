<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Internship;
use App\Models\InterviewSchedule;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RealisticApplicantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $internships = Internship::with('company')->where('status', 'published')->get();

        if ($internships->isEmpty()) {
            return; // No real internships available to apply to.
        }

        $names = [
            'Ahmad Rizky', 'Siti Nurbaya', 'Bima Satria', 'Dina Karmila',
            'Kevin Sanjaya', 'Nisa Mutiara', 'Reza Rahadian', 'Putri Ayu',
            'Rizky Febian', 'Alya Paramitha', 'Bagus Prasetyo', 'Citra Kirana',
            'Fadly Faisal', 'Gita Savitri', 'Hendra Setiawan', 'Indah Permatasari',
        ];

        $universities = [
            'Universitas Indonesia', 'Institut Teknologi Bandung', 'Universitas Gadjah Mada',
            'Universitas Brawijaya', 'Binus University', 'Universitas Diponegoro',
            'Institut Pertanian Bogor', 'Universitas Airlangga', 'Telkom University',
        ];

        $majors = [
            'Teknik Informatika', 'Sistem Informasi', 'Ilmu Komunikasi', 'Manajemen Bisnis',
            'Desain Komunikasi Visual', 'Teknik Industri', 'Akuntansi', 'Hubungan Internasional',
        ];

        $coverLetters = [
            'Saya sangat tertarik untuk melamar posisi magang ini. Melalui perkuliahan dan proyek kampus, saya telah membangun dasar yang kuat di bidang ini. Saya memiliki kemauan belajar yang tinggi dan siap berkontribusi penuh pada tim Anda.',
            'Dengan latar belakang akademis yang relevan dan ketertarikan mendalam pada industri ini, saya yakin dapat membawa perspektif baru dan tenaga yang positif bagi perusahaan. Saya berharap dapat mendiskusikan kualifikasi saya lebih lanjut.',
            'Sebagai mahasiswa tingkat akhir yang bersemangat, saya mencari kesempatan untuk mengaplikasikan ilmu yang saya pelajari di kelas ke dalam dunia industri nyata. Program magang di perusahaan Bapak/Ibu adalah tempat yang sempurna bagi saya untuk berkembang.',
            'Saya memiliki pengalaman aktif di organisasi kampus yang melatih kemampuan kepemimpinan dan komunikasi saya. Saya siap menghadapi tantangan nyata di dunia kerja melalui program magang ini.',
        ];

        $statuses = ['pending', 'reviewing', 'rejected', 'accepted'];

        $applicantCount = 0;

        foreach ($names as $name) {
            $email = Str::slug($name).rand(10, 99).'@student.ac.id';

            // Create Realistic User
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password123'),
                    'role' => 'user',
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->hasRole('user')) {
                $user->assignRole('user');
            }

            // Create Realistic User Detail
            UserDetail::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'education' => [
                        [
                            'institution' => $universities[array_rand($universities)],
                            'degree' => 'Sarjana S1',
                            'field_of_study' => $majors[array_rand($majors)],
                            'start_date' => '2022-08-01',
                            'end_date' => '2026-08-01',
                        ],
                    ],
                    'skills' => ['Microsoft Office', 'Komunikasi', 'Kerja Tim', 'Problem Solving'],
                    'bio' => 'Mahasiswa aktif yang tertarik dengan teknologi dan inovasi. Selalu siap belajar hal baru dan berkontribusi secara profesional.',
                    'phone_number' => '08'.rand(1111111111, 9999999999),
                ]
            );

            // Apply to 1-2 random internships
            $jobsToApply = $internships->random(rand(1, 2));

            foreach ($jobsToApply as $job) {
                // Ensure HR user exists for this company
                $company = $job->company;
                $hrEmail = 'hr.'.Str::slug($company->name).'@internhub.id';
                $hr = User::firstOrCreate(
                    ['email' => $hrEmail],
                    [
                        'name' => 'HR '.$company->name,
                        'password' => Hash::make('password'),
                        'role' => 'hr',
                        'email_verified_at' => now(),
                    ]
                );
                if (! $hr->hasRole('hr')) {
                    $hr->assignRole('hr');
                }

                // Assign HR to company if column exists
                if (Schema::hasColumn('users', 'company_id')) {
                    $hr->update(['company_id' => $company->id]);
                }

                $status = $statuses[array_rand($statuses)];

                $timeline = [
                    [
                        'status' => 'pending',
                        'label' => 'Lamaran Dikirim',
                        'description' => 'Lamaran Anda berhasil dikirim ke perusahaan.',
                        'date' => now()->subDays(rand(2, 10))->toDateTimeString(),
                    ],
                ];

                if ($status !== 'pending') {
                    $timeline[] = [
                        'status' => 'reviewing',
                        'label' => 'Sedang Direview',
                        'description' => 'Lamaran Anda sedang ditinjau oleh HR.',
                        'date' => now()->subDays(rand(1, 2))->toDateTimeString(),
                    ];
                }

                $application = Application::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'internship_id' => $job->id,
                    ],
                    [
                        'status' => $status,
                        'cover_letter' => $coverLetters[array_rand($coverLetters)],
                        'timeline' => $timeline,
                    ]
                );

                if ($status === 'reviewing' || $status === 'accepted') {
                    InterviewSchedule::firstOrCreate(
                        ['application_id' => $application->id],
                        [
                            'interviewer_id' => $hr->id,
                            'scheduled_at' => now()->addDays(rand(1, 5)),
                            'type' => 'online',
                            'meeting_link' => 'https://meet.google.com/'.Str::random(3).'-'.Str::random(4).'-'.Str::random(3),
                            'notes' => 'Harap persiapkan diri Anda 10 menit sebelum wawancara dimulai.',
                        ]
                    );
                }

                $applicantCount++;
            }
        }
    }
}
