<?php

use Groundhogg\Reporting\New_Reports\Chart_Contacts_By_Country;
use Groundhogg\Reporting\New_Reports\Table_Contacts_By_Country;
use Groundhogg\Reporting\New_Reports\Table_Contacts_By_Lead_Source;
use Groundhogg\Reporting\New_Reports\Table_Contacts_By_Source_Pages;
use function Groundhogg\get_contactdata;

/**
 * The reports of the places that new contacts come from only count the contacts created in the date range. The
 * range was passed to the query as a SELECT string, which was dropped with a notice, so they counted every contact.
 *
 * @see \Groundhogg\Reporting\New_Reports\Base_Report::get_new_contact_meta_totals()
 */
class Contact_Meta_Reports_Tests extends GH_UnitTestCase {

	protected $start;
	protected $end;

	public function setUp(): void {
		parent::setUp();
		$this->start = strtotime( '2026-03-01 00:00:00 UTC' );
		$this->end   = strtotime( '2026-03-31 23:59:59 UTC' );
	}

	protected function contact( string $created, array $meta ) {

		$id = self::factory()->contacts->create( [
			'email'        => 'meta-report-' . uniqid() . '@example.com',
			'date_created' => $created,
		] );

		$contact = get_contactdata( $id );
		$contact->update_meta( $meta );

		return $contact;
	}

	protected function totals( array $rows, string $alias ): array {

		$totals = [];

		foreach ( $rows as $row ) {
			$totals[ $row->$alias ] = (int) $row->total;
		}

		return $totals;
	}

	public function test_only_the_contacts_created_in_the_range_are_counted() {

		$this->contact( '2026-03-05 10:00:00', [ 'country' => 'CA' ] );
		$this->contact( '2026-03-20 10:00:00', [ 'country' => 'CA' ] );
		$this->contact( '2026-03-21 10:00:00', [ 'country' => 'US' ] );
		$this->contact( '2026-02-20 10:00:00', [ 'country' => 'US' ] ); // before
		$this->contact( '2026-04-02 10:00:00', [ 'country' => 'US' ] ); // after
		$this->contact( '2026-03-10 10:00:00', [ 'country' => '' ] ); // empty

		$table = new Table_Contacts_By_Country( $this->start, $this->end );
		$data  = $table->get_data()['data'];

		// the name of the country, then the total in a link
		$this->assertCount( 2, $data );
		$this->assertStringContainsString( '>2<', $data[0][1] );
		$this->assertStringContainsString( '>1<', $data[1][1] );
	}

	public function test_the_chart_counts_the_same_contacts() {

		$this->contact( '2026-03-05 10:00:00', [ 'country' => 'CA' ] );
		$this->contact( '2026-03-06 10:00:00', [ 'country' => 'CA' ] );
		$this->contact( '2026-02-06 10:00:00', [ 'country' => 'US' ] );

		$chart = ( new Chart_Contacts_By_Country( $this->start, $this->end ) )->get_data();

		$this->assertSame( [ 2 ], array_map( 'intval', $chart['chart']['data']['datasets'][0]['data'] ) );
	}

	public function test_lead_sources_and_source_pages_are_counted_in_the_range_too() {

		$this->contact( '2026-03-05 10:00:00', [ 'lead_source' => 'https://example.org/a', 'source_page' => 'https://example.org/page-1' ] );
		$this->contact( '2026-03-06 10:00:00', [ 'lead_source' => 'https://example.org/b', 'source_page' => 'https://example.org/page-1' ] );
		$this->contact( '2025-03-06 10:00:00', [ 'lead_source' => 'https://example.net/c', 'source_page' => 'https://example.net/page-2' ] );

		$sources = ( new Table_Contacts_By_Lead_Source( $this->start, $this->end ) )->get_data()['data'];
		$pages   = ( new Table_Contacts_By_Source_Pages( $this->start, $this->end ) )->get_data()['data'];

		$this->assertCount( 1, $sources );
		$this->assertStringContainsString( 'example.org', $sources[0][0] );
		$this->assertStringContainsString( '>2<', $sources[0][1] );

		$this->assertCount( 1, $pages );
		$this->assertStringContainsString( '/page-1', $pages[0][0] );
		$this->assertStringContainsString( '>2<', $pages[0][1] );
	}
}
