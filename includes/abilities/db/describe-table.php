<?php

namespace Groundhogg\Abilities\Db;

use Groundhogg\Abilities\Ability;
use function Groundhogg\get_db;

/**
 * Describes the tables groundhogg/query-table can query - their columns
 * (with wpdb format placeholders), primary key, and searchable (text) columns.
 * Call with no `table` to list every queryable table; with `table` to see just
 * one in detail. Cheap - no rows are read, only column metadata already held in
 * PHP.
 */
class Describe_Table extends Ability {

	protected const NAME       = 'groundhogg/describe-table';
	protected const CATEGORY   = 'groundhogg-db';
	protected const CAPABILITY = 'manage_options';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Describe Table', 'groundhogg' ),
			'description' => __( 'List the tables groundhogg/query-table can query, or describe one table\'s columns, primary key, and searchable columns. Call this before query-table rather than guessing column names.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'table' => [
						'type'        => 'string',
						'enum'        => Query_Table::ALLOWED_TABLES,
						'description' => __( 'Describe just this table. Omit to list every queryable table.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'tables' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'table' => [
									'type'        => 'string',
									'description' => __( 'Pass this as groundhogg/query-table\'s `table` param.', 'groundhogg' ),
								],
								'primary_key' => [
									'type' => 'string',
								],
								'columns' => [
									'type'                 => 'object',
									'additionalProperties' => true,
									'description'          => __( 'column name => wpdb format placeholder ("%d" int, "%s" string, "%f" float).', 'groundhogg' ),
								],
								'searchable_columns' => [
									'type'        => 'array',
									'items'       => [ 'type' => 'string' ],
									'description' => __( 'Columns query-table\'s `search` param matches against.', 'groundhogg' ),
								],
							],
							'required' => [ 'table', 'primary_key', 'columns', 'searchable_columns' ],
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$tables = ! empty( $input['table'] ) ? [ $input['table'] ] : Query_Table::ALLOWED_TABLES;

		$results = [];

		foreach ( $tables as $table ) {

			if ( ! in_array( $table, Query_Table::ALLOWED_TABLES, true ) ) {
				continue;
			}

			$db = get_db( $table );

			if ( ! $db ) {
				continue;
			}

			$results[] = [
				'table'              => $table,
				'primary_key'        => $db->get_primary_key(),
				'columns'            => $db->get_columns(),
				'searchable_columns' => $db->get_searchable_columns(),
			];
		}

		return [
			'tables' => $results,
		];
	}
}
