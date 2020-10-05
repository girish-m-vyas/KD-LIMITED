-- phpMyAdmin SQL Dump
-- version 4.8.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 29, 2020 at 07:56 AM
-- Server version: 10.1.33-MariaDB
-- PHP Version: 7.2.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kd`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(10) NOT NULL,
  `admin_name` varchar(255) NOT NULL,
  `admin_email` varchar(255) NOT NULL,
  `admin_pass` varchar(255) NOT NULL,
  `admin_about` text NOT NULL,
  `admin_contact` varchar(255) NOT NULL,
  `admin_job` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `admin_name`, `admin_email`, `admin_pass`, `admin_about`, `admin_contact`, `admin_job`) VALUES
(1, 'KD LIMITED', 'kd123@gmail.com', '123456', 'Admin', '7058663052', 'Manager');

-- --------------------------------------------------------

--
-- Table structure for table `boxes_section`
--

CREATE TABLE `boxes_section` (
  `box_id` int(10) NOT NULL,
  `box_title` text NOT NULL,
  `box_desc` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `boxes_section`
--

INSERT INTO `boxes_section` (`box_id`, `box_title`, `box_desc`) VALUES
(2, 'Free Shipping ', 'KD Limited handles everything from marketing to secure checkout and shipping'),
(3, 'Fast & Free Delivery ', 'KD Limited Provides you free and fastest Delivery.'),
(4, 'Secure Payment  ', 'we gives you secure your privacy and provides secure payment method.');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `p_id` int(10) NOT NULL,
  `ip_add` varchar(255) NOT NULL,
  `qty` int(10) NOT NULL,
  `p_price` varchar(255) NOT NULL,
  `size` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `cat_id` int(10) NOT NULL,
  `cat_title` text NOT NULL,
  `cat_top` text NOT NULL,
  `cat_image` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`cat_id`, `cat_title`, `cat_top`, `cat_image`) VALUES
(1, 'Man', 'yes', 'men.jpg'),
(2, 'Women', 'yes', 'women.jpg'),
(3, 'Kids', 'no', 'kids.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(10) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_pass` varchar(255) NOT NULL,
  `customer_city` text NOT NULL,
  `customer_contact` varchar(255) NOT NULL,
  `customer_address` text NOT NULL,
  `customer_image` text NOT NULL,
  `customer_ip` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_name`, `customer_email`, `customer_pass`, `customer_city`, `customer_contact`, `customer_address`, `customer_image`, `customer_ip`) VALUES
(7, 'girish', 'gvyas9095@gmail.com', '12345678', 'Bhiwandi', '7058663052', 'Narpoli', 'man_1.jpg', '::1'),
(8, 'vyas', 'vyas1@gmail.com', '123456', 'Bhiwandi', '9930678204', 'bhandhari', 'man_1.jpg', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `customer_orders`
--

CREATE TABLE `customer_orders` (
  `order_id` int(10) NOT NULL,
  `customer_id` int(10) NOT NULL,
  `due_amount` int(100) NOT NULL,
  `invoice_no` int(100) NOT NULL,
  `qty` int(10) NOT NULL,
  `size` text NOT NULL,
  `order_date` date NOT NULL,
  `order_status` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `customer_orders`
--

INSERT INTO `customer_orders` (`order_id`, `customer_id`, `due_amount`, `invoice_no`, `qty`, `size`, `order_date`, `order_status`) VALUES
(16, 8, 675, 898657024, 3, 'Large', '2020-09-18', 'Complete'),
(17, 7, 45, 1012651154, 1, 'Small', '2020-09-18', 'Complete'),
(18, 7, 669, 385180963, 1, 'Medium', '2020-09-18', 'pending'),
(19, 8, 1636, 1786792160, 4, 'Medium', '2020-09-18', 'Complete'),
(20, 8, 1347, 1394537470, 3, 'Medium', '2020-09-18', 'pending'),
(21, 8, 2397, 1158472429, 3, 'Medium', '2020-09-18', 'Complete'),
(22, 7, 799, 1817111509, 1, 'Medium', '2020-09-18', 'Complete'),
(23, 7, 799, 1348758881, 1, 'Medium', '2020-09-19', 'Complete'),
(24, 7, 429, 1073832067, 1, 'Medium', '2020-09-19', 'pending'),
(25, 7, 1716, 1221910581, 4, 'Medium', '2020-09-19', 'Complete');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(10) NOT NULL,
  `invoice_no` int(10) NOT NULL,
  `amount` int(10) NOT NULL,
  `payment_mode` text NOT NULL,
  `ref_no` int(10) NOT NULL,
  `code` int(10) NOT NULL,
  `payment_date` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `invoice_no`, `amount`, `payment_mode`, `ref_no`, `code`, `payment_date`) VALUES
(11, 1786792160, 1636, 'NetBanking', 12143202, 2184521, '2020-09-18'),
(12, 1158472429, 2397, 'Paypal', 123654, 232214, '2020-09-18'),
(13, 1817111509, 799, 'Paytm', 2525512, 521345321, '2020-09-18'),
(14, 1348758881, 799, 'Paypal', 2522502, 1565, '2020-09-19'),
(15, 1221910581, 1716, 'NetBanking', 324531, 643892, '2020-09-19');

-- --------------------------------------------------------

--
-- Table structure for table `pending_orders`
--

CREATE TABLE `pending_orders` (
  `order_id` int(10) NOT NULL,
  `customer_id` int(10) NOT NULL,
  `invoice_no` int(10) NOT NULL,
  `product_id` text NOT NULL,
  `qty` int(10) NOT NULL,
  `size` text NOT NULL,
  `order_status` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `pending_orders`
--

INSERT INTO `pending_orders` (`order_id`, `customer_id`, `invoice_no`, `product_id`, `qty`, `size`, `order_status`) VALUES
(16, 7, 385180963, '1', 1, 'Medium', 'pending'),
(18, 8, 1394537470, '29', 3, 'Medium', 'pending'),
(19, 8, 1158472429, '25', 3, 'Medium', 'pending'),
(20, 7, 1817111509, '25', 1, 'Medium', 'pending'),
(21, 7, 1348758881, '25', 1, 'Medium', 'pending'),
(22, 7, 1073832067, '30', 1, 'Medium', 'pending'),
(23, 7, 1221910581, '30', 4, 'Medium', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(10) NOT NULL,
  `p_cat_id` int(10) NOT NULL,
  `cat_id` int(10) NOT NULL,
  `manufacturer_id` int(10) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `product_title` text NOT NULL,
  `product_img1` text NOT NULL,
  `product_img2` text NOT NULL,
  `product_img3` text NOT NULL,
  `product_price` int(10) DEFAULT NULL,
  `product_keywords` text NOT NULL,
  `product_desc` text NOT NULL,
  `product_label` text NOT NULL,
  `product_sale` int(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `p_cat_id`, `cat_id`, `manufacturer_id`, `date`, `product_title`, `product_img1`, `product_img2`, `product_img3`, `product_price`, `product_keywords`, `product_desc`, `product_label`, `product_sale`) VALUES
(1, 1, 2, 3, '2020-09-18 11:08:04', 'Tokichoi Front Pocket Collared Dress', 'product_front.jpg', 'product-back.jpg', 'product_hang.jpg', 669, 'Dress', '<p>Just want to try update</p>', 'new', 0),
(2, 4, 3, 3, '2020-09-18 11:08:59', 'Boys Puffer Coat With Detachable Hood', 'boys-Puffer-Coat-With-Detachable-Hood-1.jpg', 'boys-Puffer-Coat-With-Detachable-Hood-2.jpg', 'boys-Puffer-Coat-With-Detachable-Hood-3.jpg', 559, 'Hood', '<p>Boys Puffer coat with detachable Hood</p>', 'new', 0),
(4, 1, 1, 2, '2020-09-18 11:05:39', 'Man Geox Winter Jacket', 'Man-Geox-Winter-jacket-1.jpg', 'Man-Geox-Winter-jacket-2.jpg', 'Man-Geox-Winter-jacket-3.jpg', 619, 'Snake Skin', '<p>Man Geox Winter Jacket</p>', 'new', 0),
(5, 1, 2, 1, '2020-09-18 11:06:47', 'Women Red Winter Jacket', 'Red-Winter-jacket-1.jpg', 'Red-Winter-jacket-2.jpg', 'Red-Winter-jacket-3.jpg', 519, 'Korean Jacket', '<p>Red Color Jakcet</p>', 'new', 0),
(6, 4, 2, 2, '2020-09-18 11:07:44', 'Woman Waxed Cotton Coat', 'waxed-cotton-coat-woman-1.jpg', 'waxed-cotton-coat-woman-2.jpg', 'waxed-cotton-coat-woman-3.jpg', 498, 'Cotton', '<p>Cotton waxed coat</p>', 'new', 0),
(14, 1, 1, 4, '2020-09-18 11:14:31', 'Levi`s Trucker Jacket', 'levis-Trucker-Jacket.jpg', 'levis-Trucker-Jacket-2.jpg', 'levis-Trucker-Jacket-3.jpg', 719, 'Trucker', '<p>Levi Denim Jacket</p>', 'new', 0),
(17, 1, 2, 2, '2020-09-18 10:57:28', 'Edited Women Jacket ', 'new-jacket-women-1.jpg', 'new-jacket-women-2.jpg', 'new-jacket-women-3.jpg', 499, 'Women Jacket', '<p>New Woman Jacket</p>', 'new', 0),
(18, 4, 2, 2, '2020-09-18 11:15:30', 'Black Swan Blouse', 'black-swan-blouse.jpg', 'swan-blouse-2.jpg', 'swan-blouse-3.jpg', 509, 'Blouse, Coat, Woman, Jacket', '<p>Black swan Blouse coat</p>', 'new', 0),
(19, 4, 1, 0, '2020-09-18 10:58:24', 'Fox Shirt', '102.jpg', '103.jpg', '', 859, 'Fox Shirt', '<p>Fox Shirt</p>', 'new', 0),
(20, 1, 1, 0, '2020-09-18 10:57:55', 'Stealth Bomber Jacket', '107.jpg', '108.jpg', '', 759, 'Stealth Bomber Jacket', '<p>Stealth Bomber Jacket</p>', 'new', 0),
(21, 5, 1, 0, '2020-09-18 11:03:43', 'Military SPCC Print T-Shirt ', '100.jpg', '101.jpg', '', 309, 'T-shirt', '<p>Military SPCC Print&nbsp;</p>', 'new', 0),
(22, 5, 1, 0, '2020-09-18 11:17:23', 'Mason T-shirt', '109.jpg', '110.jpg', '', 499, 'T-Shirt', '<p>our mason basic new t-shirt has been crafted from a premium 160gm combed cotton yarn.</p>', 'new', 0),
(23, 6, 1, 0, '2020-09-18 11:20:04', 'Trench Skinny Denim Jeans', '104.jpg', '111.jpg', '', 999, 'Jeans', '<p>Featuring the Tranch slim fit jeanss contructed from a high quality 12 ounce 2 way stretch denim fabric.</p>', 'new', 0),
(24, 6, 1, 0, '2020-09-18 11:23:31', 'Feather Slim Fit Denim Jeans', '112.jpg', '113.jpg', '', 949, 'Jeans', '<p>This Feather features a classic 5 pocket design, skillfully finished in a depp indigo wash</p>', 'new', 0),
(25, 5, 1, 0, '2020-09-18 11:27:16', 'Fox Shirt olive', '114.jpg', '115.jpg', '', 799, 'Fox Shirt', '<p>Constructed from high quality twill, the finer details include classic flap chest pockets with twill tape detail.</p>', 'new', 0),
(26, 1, 1, 0, '2020-09-18 11:37:51', 'Ranger Longer Length Hoody', '116.jpg', '117.jpg', '', 849, 'Ho0dy', '<p>featuing the Ranger Longer length pullover hoody with acid wash and front bellow pocket</p>', 'new', 0),
(27, 7, 2, 0, '2020-09-18 12:00:38', 'Imperial Tunic', '118.jpg', '119.jpeg', '', 819, 'Tunic', '<p>a painter smock with little extra length allowing it to be worn as a dress</p>', 'new', 0),
(28, 7, 2, 0, '2020-09-18 12:05:22', 'Voile V-Neck Dress in Navy', '120.jpeg', '121.jpeg', '', 949, 'Voile V-Neck Dress in Navy', '<p>Voile V-Neck Dress in Navy</p>', 'new', 0),
(29, 7, 2, 0, '2020-09-18 12:08:33', 'Cotton Long Tee in Ink', '122.jpeg', '123.jpeg', '', 449, 'Tops', '<p>Cotton Long Tee in ink</p>', 'new', 0),
(30, 7, 3, 0, '2020-09-18 12:34:41', 'Long Sleeve Scarf Dress', '124.jpg', '124.jpg', '', 429, 'Long Sleeve Scarf Dress', '<p>Long sleeve Scarf Dress</p>', 'new', 0),
(31, 7, 3, 0, '2020-09-18 12:36:31', 'Flowered Dress', '125.jpg', '125.jpg', '', 409, 'Frog', '<p>Flowered Dress</p>', 'new', 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `p_cat_id` int(10) NOT NULL,
  `p_cat_title` text NOT NULL,
  `p_cat_top` text NOT NULL,
  `p_cat_image` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`p_cat_id`, `p_cat_title`, `p_cat_top`, `p_cat_image`) VALUES
(1, 'Jackets', 'yes', 'jacket.jpg'),
(4, 'Shirt', 'yes', '102.jpg'),
(5, 'T-Shirt', 'no', 'tshirt.jpg'),
(6, 'Jeans', 'yes', '104.jpg'),
(7, 'Tops', 'yes', 'category_10.png');

-- --------------------------------------------------------

--
-- Table structure for table `slider`
--

CREATE TABLE `slider` (
  `slide_id` int(10) NOT NULL,
  `slide_name` varchar(255) NOT NULL,
  `slide_image` text NOT NULL,
  `slide_url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `slider`
--

INSERT INTO `slider` (`slide_id`, `slide_name`, `slide_image`, `slide_url`) VALUES
(8, 'Slide Number 10', 'slide-8.jpg', 'http://localhost/girish1/shop.php'),
(9, 'Slide Number 11', 'slider-5 (1).jpg', 'http://localhost/girish1/checkout.php'),
(13, 'Editing Slide 12', 'slider-number-9.jpg', 'http://localhost/girish1/contact.php'),
(14, 'Slide Number 14', 'slider-number-14.jpg', 'http://localhost/girish1/customer_register.php');

-- --------------------------------------------------------

--
-- Table structure for table `terms`
--

CREATE TABLE `terms` (
  `term_id` int(10) NOT NULL,
  `term_title` varchar(100) NOT NULL,
  `term_link` varchar(100) NOT NULL,
  `term_desc` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `terms`
--

INSERT INTO `terms` (`term_id`, `term_title`, `term_link`, `term_desc`) VALUES
(9, 'Rules & Regulations', 'link_1', '<p>Any product you return must be in the same condition you received it and in the original packaging. Please keep the receipt.</p>'),
(11, 'Refund Condition Policy', 'link_3', '<p>If you are not 100% satisfied with your purchase, you can return the product and get a full refund or exchange the product for another one, be it similar or not.You can return a product for up to 15 days from the date you purchased it.</p>\r\n<p>&nbsp;</p>\r\n<p>&nbsp;</p>');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `boxes_section`
--
ALTER TABLE `boxes_section`
  ADD PRIMARY KEY (`box_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`p_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`cat_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `customer_orders`
--
ALTER TABLE `customer_orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `pending_orders`
--
ALTER TABLE `pending_orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`p_cat_id`);

--
-- Indexes for table `slider`
--
ALTER TABLE `slider`
  ADD PRIMARY KEY (`slide_id`);

--
-- Indexes for table `terms`
--
ALTER TABLE `terms`
  ADD PRIMARY KEY (`term_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `boxes_section`
--
ALTER TABLE `boxes_section`
  MODIFY `box_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `cat_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `customer_orders`
--
ALTER TABLE `customer_orders`
  MODIFY `order_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `pending_orders`
--
ALTER TABLE `pending_orders`
  MODIFY `order_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `p_cat_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `slider`
--
ALTER TABLE `slider`
  MODIFY `slide_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `terms`
--
ALTER TABLE `terms`
  MODIFY `term_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
