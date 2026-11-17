<?php

namespace Tests\Feature;

use App\Support\MemberApplicationReturn;
use Illuminate\Http\Request;
use Tests\TestCase;

// Check that returning to the list keeps safe filters and avoids other pages.
class MemberApplicationReturnTest extends TestCase
{
    public function test_valid_filters_and_page_are_kept(): void
    {
        $request = Request::create('/field-officer/member-applications/x/reject?status=pending&page=2&sort=az');
        $url = MemberApplicationReturn::url($request, 'field-officer.member-applications.index');
        $this->assertStringContainsString('/field-officer/member-applications?', $url);
        $this->assertStringContainsString('page=2', $url);
        $this->assertStringContainsString('status=pending', $url);
    }

    public function test_invalid_or_unrelated_values_do_not_change_the_destination(): void
    {
        $request = Request::create('/association/member-applications?status=invalid&next=https://example.com');
        $this->assertSame(route('association.member-applications.index'), MemberApplicationReturn::url($request, 'association.member-applications.index'));
    }
}
