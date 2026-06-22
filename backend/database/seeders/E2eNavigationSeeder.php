<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Company;
use App\Models\Internship;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class E2eNavigationSeeder extends Seeder
{
    public const STUDENT_EMAIL = 'e2e.student@example.com';

    public const HR_EMAIL = 'e2e.hr@example.com';

    public const ADMIN_EMAIL = 'e2e.admin@example.com';

    public const MENTOR_EMAIL = 'e2e.mentor@example.com';

    public const UNVERIFIED_EMAIL = 'e2e.unverified@example.com';

    public const STUDENT_PASSWORD = 'Password123!';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('E2eNavigationSeeder is only allowed in local/testing environments.');
        }

        $this->call(RolesAndPermissionsSeeder::class);
        $this->clearLoginRateLimits();

        $student = $this->user(self::STUDENT_EMAIL, 'E2E Student', UserRole::USER);
        $hr = $this->user(self::HR_EMAIL, 'E2E HR', UserRole::HR);
        $admin = $this->user(self::ADMIN_EMAIL, 'E2E Admin', UserRole::ADMIN);
        $mentor = $this->user(self::MENTOR_EMAIL, 'E2E Mentor', UserRole::MENTOR);
        $unverified = $this->user(self::UNVERIFIED_EMAIL, 'E2E Unverified Student', UserRole::USER, false);

        $this->profile($student);
        $this->profile($unverified);

        $company = Company::updateOrCreate(
            ['slug' => 'e2e-talent-lab'],
            [
                'name' => 'E2E Talent Lab',
                'description' => 'Company seeded for browser navigation coverage.',
                'location' => 'Jakarta',
                'website' => 'https://example.com/e2e-talent-lab',
                'is_verified' => true,
            ],
        );

        $this->membership($hr, $company, 'owner');
        $this->membership($mentor, $company, 'mentor');

        $reviewInternship = Internship::updateOrCreate(
            ['slug' => 'e2e-product-design-intern'],
            [
                'company_id' => $company->id,
                'title' => 'E2E Product Design Intern',
                'description' => 'A seeded internship with an existing applicant.',
                'requirements' => 'Portfolio, communication, and product thinking.',
                'type' => 'Hybrid',
                'location' => 'Jakarta',
                'status' => 'published',
                'deadline_at' => now()->addMonth(),
            ],
        );

        Internship::updateOrCreate(
            ['slug' => 'e2e-growth-marketing-intern'],
            [
                'company_id' => $company->id,
                'title' => 'E2E Growth Marketing Intern',
                'description' => 'A seeded internship for browser apply flow coverage.',
                'requirements' => 'Copywriting, analytics, and campaign execution.',
                'type' => 'Remote',
                'location' => 'Remote',
                'status' => 'published',
                'deadline_at' => now()->addMonth(),
            ],
        );

        Application::updateOrCreate(
            [
                'user_id' => $student->id,
                'internship_id' => $reviewInternship->id,
            ],
            [
                'status' => 'accepted',
                'cover_letter' => 'Existing E2E application for HR and mentor coverage.',
                'cv_snapshot' => 'private/cvs/e2e-student.pdf',
                'mentor_user_id' => $mentor->id,
                'timeline' => [
                    [
                        'status' => 'accepted',
                        'label' => 'Lamaran Diterima',
                        'description' => 'Seeded accepted application for E2E tests.',
                        'date' => now()->toDateTimeString(),
                    ],
                ],
            ],
        );
    }

    private function user(string $email, string $name, UserRole $role, bool $verified = true): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(self::STUDENT_PASSWORD),
                'role' => $role->value,
                'email_verified_at' => $verified ? now() : null,
                'is_active' => true,
            ],
        );

        $user->syncRoles([$role->value]);

        return $user;
    }

    private function profile(User $user): void
    {
        UserDetail::updateOrCreate(
            ['user_id' => $user->id],
            [
                'bio' => 'E2E navigation test profile.',
                'address' => 'Jakarta',
                'education' => [
                    [
                        'school' => 'E2E University',
                        'degree' => 'S1',
                        'start_year' => 2022,
                        'end_year' => 2026,
                    ],
                ],
                'skills' => ['Laravel', 'Vue', 'Inertia'],
                'cv_path' => 'private/cvs/e2e-student.pdf',
            ],
        );
    }

    private function membership(User $user, Company $company, string $role): void
    {
        $user->companyMemberships()->updateOrCreate(
            ['company_id' => $company->id],
            [
                'role' => $role,
                'is_active' => true,
            ],
        );
    }

    private function clearLoginRateLimits(): void
    {
        foreach ([self::STUDENT_EMAIL, self::HR_EMAIL, self::ADMIN_EMAIL, self::MENTOR_EMAIL, self::UNVERIFIED_EMAIL] as $email) {
            foreach (['127.0.0.1', '::1'] as $ip) {
                RateLimiter::clear(Str::transliterate(Str::lower($email).'|'.$ip));
            }
        }
    }
}
