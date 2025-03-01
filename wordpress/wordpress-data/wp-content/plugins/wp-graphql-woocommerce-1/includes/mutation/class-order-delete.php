<?php
/**
 * Mutation - deleteOrder
 *
 * Registers mutation for delete an order.
 *
 * @package WPGraphQL\WooCommerce\Mutation
 * @since 0.2.0
 */

namespace WPGraphQL\WooCommerce\Mutation;

use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use WC_Order_Factory;
use WPGraphQL\AppContext;
use WPGraphQL\Utils\Utils;
use WPGraphQL\WooCommerce\Data\Mutation\Order_Mutation;
use WPGraphQL\WooCommerce\Model\Order;

/**
 * Class Order_Delete
 */
class Order_Delete {
	/**
	 * Registers mutation
	 *
	 * @return void
	 */
	public static function register_mutation() {
		register_graphql_mutation(
			'deleteOrder',
			[
				'inputFields'         => self::get_input_fields(),
				'outputFields'        => self::get_output_fields(),
				'mutateAndGetPayload' => self::mutate_and_get_payload(),
			]
		);
	}

	/**
	 * Defines the mutation input field configuration
	 *
	 * @return array
	 */
	public static function get_input_fields() {
		return array_merge(
			[
				'id'          => [
					'type'        => 'ID',
					'description' => __( 'Database ID or global ID of the order', 'wp-graphql-woocommerce' ),
				],
				'orderId'     => [
					'type'              => 'Int',
					'description'       => __( 'Order WP ID', 'wp-graphql-woocommerce' ),
					'deprecationReason' => __( 'Use "id" field instead.', 'wp-graphql-woocommerce' ),
				],
				'forceDelete' => [
					'type'        => 'Boolean',
					'description' => __( 'Delete or simply place in trash.', 'wp-graphql-woocommerce' ),
				],
			]
		);
	}

	/**
	 * Defines the mutation output field configuration
	 *
	 * @return array
	 */
	public static function get_output_fields() {
		return [
			'order' => [
				'type'    => 'Order',
				'resolve' => static function ( $payload ) {
					return $payload['order'];
				},
			],
		];
	}

	/**
	 * Defines the mutation data modification closure.
	 *
	 * @return callable
	 */
	public static function mutate_and_get_payload() {
		return static function ( $input, AppContext $context, ResolveInfo $info ) {
			// Retrieve order ID.
			$order_id = false;
			if ( ! empty( $input['id'] ) ) {
				$order_id = Utils::get_database_id_from_id( $input['id'] );
			} elseif ( ! empty( $input['orderId'] ) ) {
				$order_id = absint( $input['orderId'] );
			} else {
				throw new UserError( __( 'Order ID provided is missing or invalid. Please check input and try again.', 'wp-graphql-woocommerce' ) );
			}

			if ( ! $order_id ) {
				throw new UserError( __( 'Order ID provided is invalid. Please check input and try again.', 'wp-graphql-woocommerce' ) );
			}

			// Check if authorized to delete this order.
			if ( ! Order_Mutation::authorized( $input, $context, $info, 'delete', $order_id ) ) {
				throw new UserError( __( 'User does not have the capabilities necessary to delete an order.', 'wp-graphql-woocommerce' ) );
			}

			$force_delete = false;
			if ( ! empty( $input['forceDelete'] ) ) {
				$force_delete = $input['forceDelete'];
			}

			/**
			 * Get Order model instance for output.
			 *
			 * @var \WC_Order $order
			 */
			$order = new Order( $order_id );

			// Cache items to prevent null value errors.
			// @codingStandardsIgnoreStart
			$order->get_downloadable_items();
			$order->get_items();
			$order->get_items( 'fee' );
			$order->get_items( 'shipping' );
			$order->get_items( 'tax' );
			$order->get_items( 'coupon' );
			// @codingStandardsIgnoreEnd.

			/**
			 * Action called before order is deleted.
			 *
			 * @param \WC_Order|\WPGraphQL\WooCommerce\Model\Order $order   Order model instance.
			 * @param array           $input   Input data describing order.
			 * @param \WPGraphQL\AppContext      $context Request AppContext instance.
			 * @param \GraphQL\Type\Definition\ResolveInfo     $info    Request ResolveInfo instance.
			 */
			do_action( 'graphql_woocommerce_before_order_delete', $order, $input, $context, $info );

			// Delete order.
			$order_to_be_deleted = WC_Order_Factory::get_order( $order->get_id() );

			if ( ! is_object( $order_to_be_deleted ) ) {
				throw new UserError( __( 'Order to be deleted could not be found.', 'wp-graphql-woocommerce' ) );
			}

			$success = Order_Mutation::purge( $order_to_be_deleted, $force_delete );

			if ( ! $success ) {
				throw new UserError(
					sprintf(
						/* translators: Deletion failed message */
						__( 'Removal of Order %d failed', 'wp-graphql-woocommerce' ),
						$order->get_id()
					)
				);
			}

			/**
			 * Action called before order is deleted.
			 *
			 * @param \WC_Order|\WPGraphQL\WooCommerce\Model\Order $order   Order model instance.
			 * @param array           $input   Input data describing order
			 * @param \WPGraphQL\AppContext      $context Request AppContext instance.
			 * @param \GraphQL\Type\Definition\ResolveInfo     $info    Request ResolveInfo instance.
			 */
			do_action( 'graphql_woocommerce_after_order_delete', $order, $input, $context, $info );

			return [ 'order' => $order ];
		};
	}
}
