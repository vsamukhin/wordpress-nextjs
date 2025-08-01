<?php
/**
 * This file contains access functions for various class methods
 *
 * @package WPGraphQL
 * @since 0.0.2
 */

use GraphQL\Type\Definition\Type;
use WPGraphQL\Registry\TypeRegistry;
use WPGraphQL\Request;
use WPGraphQL\Router;
use WPGraphQL\Utils\Utils;

/**
 * Formats a string for use as a GraphQL name.
 *
 * Per the GraphQL spec, characters in names are limited to Latin ASCII letter, digits, or underscores.
 *
 * @see http://spec.graphql.org/draft/#sec-Names
 * @uses graphql_pre_format_name filter.
 *
 * @param string $name The name to format.
 * @param string $replacement The replacement character for invalid characters. Defaults to '_'.
 * @param string $regex The regex to use to match invalid characters. Defaults to '/[^A-Za-z0-9_]/i'.
 *
 * @since v1.17.0
 */
function graphql_format_name( string $name, string $replacement = '_', string $regex = '/[^A-Za-z0-9_]/i' ): string {
	return Utils::format_graphql_name( $name, $replacement, $regex );
}

/**
 * Formats the name of a field so that it plays nice with GraphiQL
 *
 * @param string $field_name Name of the field
 *
 * @return string Name of the field
 * @since  0.0.2
 *
 * @todo refactor to use Utils::format_field_name()
 */
function graphql_format_field_name( $field_name ): string {
	// Bail if empty.
	if ( empty( $field_name ) ) {
		return '';
	}

	// First strip out the non-alphanumeric characters.
	$formatted_field_name = graphql_format_name( $field_name, ' ', '/[^A-Za-z0-9]/i' );

	// If the field name is empty, return the original field name for the error.
	if ( empty( $formatted_field_name ) ) {
		return $field_name;
	}

	// Then convert string to camelCase.
	return str_replace( ' ', '', lcfirst( ucwords( $formatted_field_name ) ) );
}

/**
 * Formats the name of a Type so that it plays nice with GraphiQL
 *
 * @param string $type_name Name of the field
 *
 * @return string Name of the field
 * @since  0.0.2
 */
function graphql_format_type_name( $type_name ): string {
	// Bail if empty.
	if ( empty( $type_name ) ) {
		return '';
	}

	$formatted_type_name = graphql_format_name( $type_name, ' ', '/[^A-Za-z0-9]/i' );

	// If the field name is empty, return the original field name for the error.
	if ( empty( $formatted_type_name ) ) {
		return $type_name;
	}

	// Then convert the string to PascalCase.
	return str_replace( ' ', '', ucfirst( ucwords( $formatted_type_name ) ) );
}

/**
 * Provides a simple way to run a GraphQL query without posting a request to the endpoint.
 *
 * @param array<string,mixed> $request_data   The GraphQL request data (query, variables, operation_name).
 * @param bool                $return_request If true, return the Request object, else return the results of the request execution
 *
 * @return array<string,mixed>|\WPGraphQL\Request
 * @phpstan-return ( $return_request is true ? \WPGraphQL\Request : array<string,mixed> )
 *
 * @throws \Exception
 * @since  0.2.0
 */
function graphql( array $request_data = [], bool $return_request = false ) {
	$request = new Request( $request_data );

	// allow calls to graphql() to return the full Request instead of
	// just the results of the request execution
	if ( true === $return_request ) {
		return $request;
	}

	return $request->execute();
}

/**
 * Previous access function for running GraphQL queries directly. This function will
 * eventually be deprecated in favor of `graphql`.
 *
 * @param string              $query          The GraphQL query to run
 * @param string              $operation_name The name of the operation
 * @param array<string,mixed> $variables      Variables to be passed to your GraphQL request
 * @param bool                $return_request If true, return the Request object, else return the results of the request execution
 *
 * @return array<string,mixed>|\WPGraphQL\Request
 *
 * @phpstan-return ( $return_request is true ? \WPGraphQL\Request : array<string,mixed> )
 *
 * @throws \Exception
 * @since  0.0.2
 */
function do_graphql_request( $query, $operation_name = '', $variables = [], $return_request = false ) {
	return graphql(
		[
			'query'          => $query,
			'variables'      => $variables,
			'operation_name' => $operation_name,
		],
		$return_request
	);
}

/**
 * Determine when to register types.
 *
 * @return 'graphql_register_initial_types'|'graphql_register_types'|'graphql_register_types_late'
 */
function get_graphql_register_action(): string {
	$action = 'graphql_register_types_late';
	if ( ! did_action( 'graphql_register_initial_types' ) ) {
		$action = 'graphql_register_initial_types';
	} elseif ( ! did_action( 'graphql_register_types' ) ) {
		$action = 'graphql_register_types';
	}

	return $action;
}

/**
 * Given a type name and interface name, this applies the interface to the Type.
 *
 * Should be used at the `graphql_register_types` hook.
 *
 * @param string|string[] $interface_names Array of one or more names of the GraphQL Interfaces to apply to the GraphQL Types
 * @param string|string[] $type_names      Array of one or more names of the GraphQL Types to apply the interfaces to.
 *
 * Example:
 * The following would register the "MyNewInterface" interface to the Post and Page type in the
 * Schema.
 *
 * register_graphql_interfaces_to_types( [ 'MyNewInterface' ], [ 'Post', 'Page' ] );
 */
function register_graphql_interfaces_to_types( $interface_names, $type_names ): void {
	// Bail if no interfaces or types.
	if ( empty( $type_names ) || empty( $interface_names ) ) {
		return;
	}

	if ( is_string( $type_names ) ) {
		$type_names = [ $type_names ];
	}

	if ( is_string( $interface_names ) ) {
		$interface_names = [ $interface_names ];
	}

	// Bail if they're still not arrays.
	if ( ! is_array( $type_names ) || ! is_array( $interface_names ) ) {
		return;
	}

	foreach ( $type_names as $type_name ) {
		// Filter the GraphQL Object Type Interface to apply the interface
		add_filter(
			'graphql_type_interfaces',
			static function ( $interfaces, $config ) use ( $type_name, $interface_names ) {
				$interfaces = is_array( $interfaces ) ? $interfaces : [];

				if ( strtolower( $type_name ) === strtolower( $config['name'] ) ) {
					$interfaces = array_unique( array_merge( $interfaces, $interface_names ) );
				}

				return $interfaces;
			},
			10,
			2
		);
	}
}

/**
 * Given a Type Name and a $config array, this adds a Type to the TypeRegistry
 *
 * @param string              $type_name The name of the Type to register
 * @param array<string,mixed> $config    The Type config
 */
function register_graphql_type( string $type_name, array $config ): void {
	add_action(
		get_graphql_register_action(),
		static function ( TypeRegistry $type_registry ) use ( $type_name, $config ): void {
			$type_registry->register_type( $type_name, $config );
		},
		10
	);
}

/**
 * Given a Type Name and a $config array, this adds an Interface Type to the TypeRegistry
 *
 * @param string              $type_name The name of the Type to register
 * @param array<string,mixed> $config    The Type config
 */
function register_graphql_interface_type( string $type_name, $config ): void {
	add_action(
		get_graphql_register_action(),
		static function ( TypeRegistry $type_registry ) use ( $type_name, $config ): void {
			$type_registry->register_interface_type( $type_name, $config );
		},
		10
	);
}

/**
 * Given a Type Name and a $config array, this adds an ObjectType to the TypeRegistry
 *
 * @param string              $type_name The name of the Type to register
 * @param array<string,mixed> $config    The Type config
 */
function register_graphql_object_type( string $type_name, array $config ): void {
	$config['kind'] = 'object';
	register_graphql_type( $type_name, $config );
}

/**
 * Given a Type Name and a $config array, this adds an InputType to the TypeRegistry
 *
 * @param string              $type_name The name of the Type to register
 * @param array<string,mixed> $config    The Type config
 */
function register_graphql_input_type( string $type_name, array $config ): void {
	$config['kind'] = 'input';
	register_graphql_type( $type_name, $config );
}

/**
 * Given a Type Name and a $config array, this adds an UnionType to the TypeRegistry
 *
 * @param string              $type_name The name of the Type to register
 * @param array<string,mixed> $config    The Type config
 *
 * @throws \Exception
 */
function register_graphql_union_type( string $type_name, array $config ): void {
	add_action(
		get_graphql_register_action(),
		static function ( TypeRegistry $type_registry ) use ( $type_name, $config ): void {
			$config['kind'] = 'union';
			$type_registry->register_type( $type_name, $config );
		},
		10
	);
}

/**
 * Given a Type Name and a $config array, this adds an EnumType to the TypeRegistry
 *
 * @param string              $type_name The name of the Type to register
 * @param array<string,mixed> $config    The Type config
 *
 * @phpstan-param array{
 *   description?: string|callable():string|null,
 *   values: array<string, array{
 *     name?: string,
 *     value?: mixed,
 *     deprecationReason?: string|callable():string|null,
 *     description?: string|callable():string|null,
 *     astNode?: \GraphQL\Language\AST\EnumValueDefinitionNode|null
 *   }>,
 *   astNode?: \GraphQL\Language\AST\EnumTypeDefinitionNode|null,
 *   extensionASTNodes?: \GraphQL\Language\AST\EnumTypeExtensionNode[]|null,
 *   kind?: 'enum'|null
 * } $config
 */
function register_graphql_enum_type( string $type_name, array $config ): void {
	$config['kind'] = 'enum';
	register_graphql_type( $type_name, $config );
}

/**
 * Given a Type Name, Field Name, and a $config array, this adds a Field to a registered Type in
 * the TypeRegistry
 *
 * @param string              $type_name  The name of the Type to add the field to
 * @param string              $field_name The name of the Field to add to the Type
 * @param array<string,mixed> $config     The Type config
 *
 * @throws \Exception
 * @since 0.1.0
 */
function register_graphql_field( string $type_name, string $field_name, array $config ): void {
	add_action(
		get_graphql_register_action(),
		static function ( TypeRegistry $type_registry ) use ( $type_name, $field_name, $config ): void {
			$type_registry->register_field( $type_name, $field_name, $config );
		},
		10
	);
}

/**
 * Given a Type Name and an array of field configs, this adds the fields to the registered type in
 * the TypeRegistry
 *
 * @param string                            $type_name The name of the Type to add the fields to
 * @param array<string,array<string,mixed>> $fields    An array of field configs
 *
 * @throws \Exception
 * @since 0.1.0
 */
function register_graphql_fields( string $type_name, array $fields ): void {
	add_action(
		get_graphql_register_action(),
		static function ( TypeRegistry $type_registry ) use ( $type_name, $fields ): void {
			$type_registry->register_fields( $type_name, $fields );
		},
		10
	);
}

/**
 * Adds a field to the Connection Edge between the provided 'From' Type Name and 'To' Type Name.
 *
 * @param string              $from_type  The name of the Type the connection is coming from.
 * @param string              $to_type    The name of the Type or Alias (the connection config's `FromFieldName`) the connection is going to.
 * @param string              $field_name The name of the field to add to the connection edge.
 * @param array<string,mixed> $config     The field config.
 *
 * @since 1.13.0
 */
function register_graphql_edge_field( string $from_type, string $to_type, string $field_name, array $config ): void {
	$connection_name = ucfirst( $from_type ) . 'To' . ucfirst( $to_type ) . 'ConnectionEdge';

	add_action(
		get_graphql_register_action(),
		static function ( TypeRegistry $type_registry ) use ( $connection_name, $field_name, $config ): void {
			$type_registry->register_field( $connection_name, $field_name, $config );
		},
		10
	);
}

/**
 * Adds several fields to the Connection Edge between the provided 'From' Type Name and 'To' Type Name.
 *
 * @param string                            $from_type The name of the Type the connection is coming from.
 * @param string                            $to_type   The name of the Type or Alias (the connection config's `FromFieldName`) the connection is going to.
 * @param array<string,array<string,mixed>> $fields    An array of field configs.
 *
 * @since 1.13.0
 */
function register_graphql_edge_fields( string $from_type, string $to_type, array $fields ): void {
	$connection_name = ucfirst( $from_type ) . 'To' . ucfirst( $to_type ) . 'ConnectionEdge';

	add_action(
		get_graphql_register_action(),
		static function ( TypeRegistry $type_registry ) use ( $connection_name, $fields ): void {
			$type_registry->register_fields( $connection_name, $fields );
		},
		10
	);
}

/**
 * Adds an input field to the Connection Where Args between the provided 'From' Type Name and 'To' Type Name.
 *
 * @param string              $from_type  The name of the Type the connection is coming from.
 * @param string              $to_type    The name of the Type or Alias (the connection config's `FromFieldName`) the connection is going to.
 * @param string              $field_name The name of the field to add to the connection edge.
 * @param array<string,mixed> $config      The field config.
 *
 * @since 1.13.0
 */
function register_graphql_connection_where_arg( string $from_type, string $to_type, string $field_name, array $config ): void {
	$connection_name = ucfirst( $from_type ) . 'To' . ucfirst( $to_type ) . 'ConnectionWhereArgs';

	add_action(
		get_graphql_register_action(),
		static function ( TypeRegistry $type_registry ) use ( $connection_name, $field_name, $config ): void {
			$type_registry->register_field( $connection_name, $field_name, $config );
		},
		10
	);
}

/**
 * Adds several input fields to the Connection Where Args between the provided 'From' Type Name and 'To' Type Name.
 *
 * @param string                            $from_type The name of the Type the connection is coming from.
 * @param string                            $to_type   The name of the Type or Alias (the connection config's `FromFieldName`) the connection is going to.
 * @param array<string,array<string,mixed>> $fields    An array of field configs.
 *
 * @since 1.13.0
 */
function register_graphql_connection_where_args( string $from_type, string $to_type, array $fields ): void {
	$connection_name = ucfirst( $from_type ) . 'To' . ucfirst( $to_type ) . 'ConnectionWhereArgs';

	add_action(
		get_graphql_register_action(),
		static function ( TypeRegistry $type_registry ) use ( $connection_name, $fields ): void {
			$type_registry->register_fields( $connection_name, $fields );
		},
		10
	);
}

/**
 * Renames a GraphQL field.
 *
 * @param string $type_name       Name of the Type to rename a field on.
 * @param string $field_name      Field name to be renamed.
 * @param string $new_field_name  New field name.
 *
 * @since 1.3.4
 */
function rename_graphql_field( string $type_name, string $field_name, string $new_field_name ): void {
	// Rename fields on the type.
	add_filter(
		"graphql_{$type_name}_fields",
		static function ( $fields ) use ( $field_name, $new_field_name ) {
			// Bail if the field doesn't exist.
			if ( ! isset( $fields[ $field_name ] ) ) {
				return $fields;
			}

			$fields[ $new_field_name ] = $fields[ $field_name ];
			unset( $fields[ $field_name ] );

			return $fields;
		}
	);

	// Rename fields registered to the type by connections.
	add_filter(
		"graphql_wp_connection_{$type_name}_from_field_name",
		static function ( $old_field_name ) use ( $field_name, $new_field_name ) {
			// Bail if the field name doesn't match.
			if ( $old_field_name !== $field_name ) {
				return $old_field_name;
			}

			return $new_field_name;
		}
	);
}

/**
 * Renames a GraphQL Type in the Schema.
 *
 * @param string $type_name The name of the Type in the Schema to rename.
 * @param string $new_type_name  The new name to give the Type.
 *
 * @throws \Exception
 *
 * @since 1.3.4
 */
function rename_graphql_type( string $type_name, string $new_type_name ): void {
	add_filter(
		'graphql_type_name',
		static function ( $name ) use ( $type_name, $new_type_name ) {
			if ( $name === $type_name ) {
				return $new_type_name;
			}
			return $name;
		}
	);

	// Add the new type to the registry referencing the original Type instance.
	// This allows for both the new type name and the old type name to be
	// referenced as the type when registering fields.
	add_action(
		'graphql_register_types_late',
		static function ( TypeRegistry $type_registry ) use ( $type_name, $new_type_name ): void {
			$type = $type_registry->get_type( $type_name );
			if ( ! $type instanceof Type ) {
				return;
			}
			$type_registry->register_type( $new_type_name, $type );
		}
	);
}

/**
 * Given a config array for a connection, this registers a connection by creating all appropriate
 * fields and types for the connection
 *
 * @param array<string,mixed> $config Array to configure the connection
 *
 * @since 0.1.0
 */
function register_graphql_connection( array $config ): void {
	add_action(
		get_graphql_register_action(),
		static function ( TypeRegistry $type_registry ) use ( $config ): void {
			$type_registry->register_connection( $config );
		},
		20
	);
}

/**
 * Given a Mutation Name and Config array, this adds a Mutation to the Schema
 *
 * @param string              $mutation_name The name of the Mutation to register
 * @param array<string,mixed> $config        The config for the mutation
 *
 * @since 0.1.0
 */
function register_graphql_mutation( string $mutation_name, array $config ): void {
	add_action(
		get_graphql_register_action(),
		static function ( TypeRegistry $type_registry ) use ( $mutation_name, $config ): void {
			$type_registry->register_mutation( $mutation_name, $config );
		},
		10
	);
}

/**
 * Given a config array for a custom Scalar, this registers a Scalar for use in the Schema
 *
 * @param string              $type_name The name of the Type to register
 * @param array<string,mixed> $config    The config for the scalar type to register
 *
 * @phpstan-param array{
 *   description?: string|callable():string|null,
 *   serialize?: callable(mixed): mixed,
 *   parseValue?: callable(mixed): mixed,
 *   parseLiteral?: callable(\GraphQL\Language\AST\ValueNode&\GraphQL\Language\AST\Node, array<string, mixed>|null): mixed,
 *   astNode?: \GraphQL\Language\AST\ScalarTypeDefinitionNode|null,
 *   extensionASTNodes?: array<\GraphQL\Language\AST\ScalarTypeDefinitionNode>|null
 * } $config
 *
 * @since 0.8.4
 */
function register_graphql_scalar( string $type_name, array $config ): void {
	$config['kind'] = 'scalar';
	register_graphql_type( $type_name, $config );
}

/**
 * Given a Type Name, this removes the type from the entire schema
 *
 * @param string $type_name The name of the Type to remove.
 *
 * @since 1.13.0
 */
function deregister_graphql_type( string $type_name ): void {
	// Prevent the type from being registered to the scheme directly.
	add_filter(
		'graphql_excluded_types',
		static function ( $excluded_types ) use ( $type_name ): array {
			// Normalize the types to prevent case sensitivity issues.
			$type_name = strtolower( $type_name );
			// If the type isn't already excluded, add it to the array.
			if ( ! in_array( $type_name, $excluded_types, true ) ) {
				$excluded_types[] = $type_name;
			}

			return $excluded_types;
		},
		10
	);

	// Prevent the type from being inherited as an interface.
	add_filter(
		'graphql_type_interfaces',
		static function ( $interfaces ) use ( $type_name ): array {
			// Normalize the needle and haystack to prevent case sensitivity issues.
			$key = array_search(
				strtolower( $type_name ),
				array_map( 'strtolower', $interfaces ),
				true
			);
			// If the type is found, unset it.
			if ( false !== $key ) {
				unset( $interfaces[ $key ] );
			}

			return $interfaces;
		},
		10
	);
}

/**
 * Given a Type Name and Field Name, this removes the field from the TypeRegistry
 *
 * @param string $type_name  The name of the Type to remove the field from
 * @param string $field_name The name of the field to remove
 *
 * @since 0.1.0
 */
function deregister_graphql_field( string $type_name, string $field_name ): void {
	add_action(
		get_graphql_register_action(),
		static function ( TypeRegistry $type_registry ) use ( $type_name, $field_name ): void {
			$type_registry->deregister_field( $type_name, $field_name );
		},
		10
	);
}

/**
 * Given a Connection Name, this removes the connection from the Schema
 *
 * @param string $connection_name The name of the Connection to remove
 *
 * @since 1.14.0
 */
function deregister_graphql_connection( string $connection_name ): void {
	add_action(
		get_graphql_register_action(),
		static function ( TypeRegistry $type_registry ) use ( $connection_name ): void {
			$type_registry->deregister_connection( $connection_name );
		},
		10
	);
}

/**
 * Given a Mutation Name, this removes the mutation from the Schema
 *
 * @param string $mutation_name The name of the Mutation to remove
 *
 * @since 1.14.0
 */
function deregister_graphql_mutation( string $mutation_name ): void {
	add_action(
		get_graphql_register_action(),
		static function ( TypeRegistry $type_registry ) use ( $mutation_name ): void {
			$type_registry->deregister_mutation( $mutation_name );
		},
		10
	);
}

/**
 * Whether a GraphQL request is in action or not. This is determined by the WPGraphQL Request
 * class being initiated. True while a request is in action, false after a request completes.
 *
 * This should be used when a condition needs to be checked for ALL GraphQL requests, such
 * as filtering WP_Query for GraphQL requests, for example.
 *
 * Default false.
 *
 * @since 0.4.1
 */
function is_graphql_request(): bool {
	return WPGraphQL::is_graphql_request();
}

/**
 * Whether a GraphQL HTTP request is in action or not. This is determined by
 * checking if the request is occurring on the route defined for the GraphQL endpoint.
 *
 * This conditional should only be used for features that apply to HTTP requests. If you are going
 * to apply filters to underlying WordPress core functionality that should affect _all_ GraphQL
 * requests, you should use "is_graphql_request" but if you need to apply filters only if the
 * GraphQL request is an HTTP request, use this conditional.
 *
 * Default false.
 *
 * @since 0.4.1
 */
function is_graphql_http_request(): bool {
	return Router::is_graphql_http_request();
}

/**
 * Registers a GraphQL Settings Section
 *
 * @param string              $slug   The slug of the group being registered
 * @param array<string,mixed> $config Array configuring the section. Should include: title
 *
 * @since 0.13.0
 */
function register_graphql_settings_section( string $slug, array $config ): void {
	add_action(
		'graphql_init_settings',
		static function ( \WPGraphQL\Admin\Settings\SettingsRegistry $registry ) use ( $slug, $config ): void {
			$registry->register_section( $slug, $config );
		}
	);
}

/**
 * Registers a GraphQL Settings Field
 *
 * @param string              $group  The name of the group to register a setting field to
 * @param array<string,mixed> $config The config for the settings field being registered
 *
 * @since 0.13.0
 */
function register_graphql_settings_field( string $group, array $config ): void {
	add_action(
		'graphql_init_settings',
		static function ( \WPGraphQL\Admin\Settings\SettingsRegistry $registry ) use ( $group, $config ): void {
			$registry->register_field( $group, $config );
		}
	);
}

/**
 * Given a message and an optional config array
 *
 * @param mixed|string|mixed[] $message The debug message
 * @param array<string,mixed>  $config  The debug config. Should be an associative array of keys and values.
 *                                      $config['type'] will set the "type" of the log, default type is GRAPHQL_DEBUG.
 *                                      Other fields added to $config will be merged into the debug entry.
 *
 * @since 0.14.0
 */
function graphql_debug( $message, $config = [] ): void {

	// Bail if debug is disabled.
	if ( ! WPGraphQL::debug() ) {
		return;
	}

	$debug_backtrace     = debug_backtrace(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
	$config['backtrace'] = ! empty( $debug_backtrace )
		?
		array_values(
			array_map(
				static function ( $trace ) {
					$line = isset( $trace['line'] ) ? absint( $trace['line'] ) : 0;
					return sprintf( '%s:%d', $trace['file'], $line );
				},
				array_filter( // Filter out steps without files
					$debug_backtrace,
					static function ( $step ) {
						return ! empty( $step['file'] );
					}
				)
			)
		)
		:
		[];

	add_action(
		'graphql_get_debug_log',
		static function ( \WPGraphQL\Utils\DebugLog $debug_log ) use ( $message, $config ): void {
			$debug_log->add_log_entry( $message, $config );
		}
	);
}

/**
 * Check if the name is valid for use in GraphQL
 *
 * @param string $type_name The name of the type to validate
 *
 * @since 0.14.0
 */
function is_valid_graphql_name( string $type_name ): bool {
	if ( preg_match( '/^\d/', $type_name ) ) {
		return false;
	}

	return true;
}

/**
 * Registers a series of GraphQL Settings Fields
 *
 * @param string                $group  The name of the settings group to register fields to
 * @param array<string,mixed>[] $fields Array of field configs to register to the group
 *
 * @since 0.13.0
 */
function register_graphql_settings_fields( string $group, array $fields ): void {
	add_action(
		'graphql_init_settings',
		static function ( \WPGraphQL\Admin\Settings\SettingsRegistry $registry ) use ( $group, $fields ): void {
			$registry->register_fields( $group, $fields );
		}
	);
}

/**
 * Get an option value from GraphQL settings
 *
 * @param string $option_name   The key of the option to return
 * @param mixed  $default_value The default value the setting should return if no value is set
 * @param string $section_name  The settings group section that the option belongs to
 *
 * @return mixed|string|int|bool
 * @since 0.13.0
 */
function get_graphql_setting( string $option_name, $default_value = '', $section_name = 'graphql_general_settings' ) {
	$section_fields = get_option( $section_name, [] );

	/**
	 * Filter the section fields
	 *
	 * @param array<string,mixed> $section_fields The values of the fields stored for the section
	 * @param string              $section_name   The name of the section
	 * @param mixed               $default_value  The default value for the option being retrieved
	 */
	$section_fields = apply_filters( 'graphql_get_setting_section_fields', $section_fields, $section_name, $default_value );

	// ensure the filtered sections fields are an array before proceeding
	$section_fields = is_array( $section_fields ) ? $section_fields : [];

	/**
	 * Get the value from the stored data, or return the default
	 */
	$value = $section_fields[ $option_name ] ?? $default_value;

	/**
	 * Filter the value before returning it
	 *
	 * @param mixed               $value          The value of the field
	 * @param mixed               $default_value  The default value if there is no value set
	 * @param string              $option_name    The name of the option
	 * @param array<string,mixed> $section_fields The setting values within the section
	 * @param string              $section_name   The name of the section the setting belongs to
	 */
	return apply_filters( 'graphql_get_setting_section_field_value', $value, $default_value, $option_name, $section_fields, $section_name );
}

/**
 * Get the endpoint route for the WPGraphQL API
 *
 * @since 1.12.0
 */
function graphql_get_endpoint(): string {

	// get the endpoint from the settings. default to 'graphql'
	$endpoint = get_graphql_setting( 'graphql_endpoint', 'graphql' );

	/**
	 * @param string $endpoint The relative endpoint that graphql can be accessed at
	 */
	$filtered_endpoint = apply_filters( 'graphql_endpoint', $endpoint );

	// If the filtered endpoint has a value (not filtered to a falsy value), use it. else return the default endpoint
	return is_string( $filtered_endpoint ) && ! empty( $filtered_endpoint ) ? $filtered_endpoint : $endpoint;
}

/**
 * Return the full url for the GraphQL Endpoint.
 *
 * @since 1.12.0
 */
function graphql_get_endpoint_url(): string {
	return (string) site_url( graphql_get_endpoint() );
}

/**
 * Polyfill for PHP versions below 8.0
 */
if ( ! function_exists( 'str_starts_with' ) ) {

	/**
	 * @param string $haystack
	 * @param string $needle
	 */
	function str_starts_with( string $haystack, string $needle ): bool {
		return 0 === strncmp( $haystack, $needle, strlen( $needle ) );
	}
}

/**
 * Polyfill for PHP versions below 8.0
 */
if ( ! function_exists( 'str_ends_with' ) ) {

	/**
	 * @param string $haystack
	 * @param string $needle
	 */
	function str_ends_with( string $haystack, string $needle ): bool {
		if ( '' === $needle || $needle === $haystack ) {
			return true;
		}

		if ( '' === $haystack ) {
			return false;
		}

		$needle_length = strlen( $needle );

		return $needle_length <= strlen( $haystack ) && 0 === substr_compare( $haystack, $needle, -$needle_length );
	}
}

/**
 * @param string              $slug A unique slug to identify the admin notice by
 * @param array<string,mixed> $config The config for the admin notice. Determines visibility, context, etc.
 *
 * @phpstan-param array{
 *  message: string,
 *  type?: 'error'|'warning'|'success'|'info',
 *  is_dismissable?: bool,
 *  conditions?: callable():bool
 * } $config
 */
function register_graphql_admin_notice( string $slug, array $config ): void {
	add_action(
		'graphql_admin_notices_init',
		static function ( \WPGraphQL\Admin\AdminNotices $admin_notices ) use ( $slug, $config ): void {
			$admin_notices->add_admin_notice( $slug, $config );
		}
	);
}

/**
 * Get the admin notices registered for the WPGraphQL plugin screens
 *
 * @return array<string,array{
 *  message: string,
 *  type?: 'error'|'warning'|'success'|'info',
 *  is_dismissable?: bool,
 *  conditions?: callable():bool,
 * }>
 */
function get_graphql_admin_notices(): array {
	$admin_notices = \WPGraphQL\Admin\AdminNotices::get_instance();
	return $admin_notices->get_admin_notices();
}
