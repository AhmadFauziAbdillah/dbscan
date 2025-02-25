/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 100424
 Source Host           : localhost:3306
 Source Schema         : diabetes_clustering

 Target Server Type    : MySQL
 Target Server Version : 100424
 File Encoding         : 65001

 Date: 25/02/2025 05:56:57
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for clustering_results
-- ----------------------------
DROP TABLE IF EXISTS `clustering_results`;
CREATE TABLE `clustering_results`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cluster_count` int(11) NOT NULL,
  `epsilon` float NOT NULL,
  `min_points` int(11) NOT NULL,
  `data_points` int(11) NOT NULL,
  `outliers` int(11) NOT NULL,
  `execution_time` float NOT NULL,
  `date_generated` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 19 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of clustering_results
-- ----------------------------
INSERT INTO `clustering_results` VALUES (5, 3, 0, 0, 0, 0, 0, '2024-10-11 03:54:44');
INSERT INTO `clustering_results` VALUES (6, 2, 0, 0, 0, 0, 0, '2024-10-11 03:55:45');
INSERT INTO `clustering_results` VALUES (7, 1, 0, 0, 0, 0, 0, '2024-10-11 04:31:35');
INSERT INTO `clustering_results` VALUES (8, 1, 0, 0, 0, 0, 0, '2024-10-11 04:35:40');
INSERT INTO `clustering_results` VALUES (9, 1, 0, 0, 0, 0, 0, '2024-10-11 04:35:56');
INSERT INTO `clustering_results` VALUES (10, 1, 0, 0, 0, 0, 0, '2024-10-11 05:36:45');
INSERT INTO `clustering_results` VALUES (11, 1, 0, 0, 0, 0, 0, '2024-10-11 05:37:52');
INSERT INTO `clustering_results` VALUES (12, 1, 0, 0, 0, 0, 0, '2024-10-11 05:38:04');
INSERT INTO `clustering_results` VALUES (13, 1, 0, 0, 0, 0, 0, '2024-10-11 05:41:03');
INSERT INTO `clustering_results` VALUES (14, 1, 0, 0, 0, 0, 0, '2024-10-11 05:44:49');
INSERT INTO `clustering_results` VALUES (15, 1, 0, 0, 0, 0, 0, '2024-10-11 05:46:19');
INSERT INTO `clustering_results` VALUES (16, 1, 0, 0, 0, 0, 0, '2024-10-11 05:46:33');
INSERT INTO `clustering_results` VALUES (17, 2, 0, 0, 0, 0, 0, '2024-10-11 05:46:45');
INSERT INTO `clustering_results` VALUES (18, 2, 0, 0, 0, 0, 0, '2024-11-01 12:25:45');

-- ----------------------------
-- Table structure for diabetes_data
-- ----------------------------
DROP TABLE IF EXISTS `diabetes_data`;
CREATE TABLE `diabetes_data`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wilayah` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jumlah_penderita` int(11) NOT NULL,
  `jumlah_kematian` int(11) NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `cluster` int(11) NULL DEFAULT NULL,
  `tahun` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 36 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of diabetes_data
-- ----------------------------
INSERT INTO `diabetes_data` VALUES (1, 'Aceh', 857609, 7232, '2024-10-11 04:39:58', 1, 2022);
INSERT INTO `diabetes_data` VALUES (2, 'Sumatera Utara', 675390, 3637, '2024-10-11 04:39:58', 2, 2022);
INSERT INTO `diabetes_data` VALUES (3, 'Sumatera Barat', 971039, 4558, '2024-10-11 04:39:58', 2, 2022);
INSERT INTO `diabetes_data` VALUES (4, 'Riau', 814112, 3046, '2024-10-11 04:39:58', 2, 2023);
INSERT INTO `diabetes_data` VALUES (5, 'Jambi', 153197, 9101, '2024-10-11 04:39:58', 2, 2023);
INSERT INTO `diabetes_data` VALUES (6, 'Sumatera Selatan', 152982, 9595, '2024-10-11 04:39:58', 2, 2023);
INSERT INTO `diabetes_data` VALUES (7, 'Bengkulu', 620782, 8315, '2024-10-11 04:39:58', 2, 2023);
INSERT INTO `diabetes_data` VALUES (8, 'Lampung', 716378, 3520, '2024-10-11 04:39:58', 2, 2023);
INSERT INTO `diabetes_data` VALUES (9, 'Kepulauan Bangka Belitung', 554066, 9551, '2024-10-11 04:39:58', 2, 2023);
INSERT INTO `diabetes_data` VALUES (10, 'Kepulauan Riau', 371170, 8211, '2024-10-11 04:39:58', 2, 2023);
INSERT INTO `diabetes_data` VALUES (11, 'DKI Jakarta', 948840, 8713, '2024-10-11 04:39:58', 2, 2023);
INSERT INTO `diabetes_data` VALUES (12, 'Jawa Barat', 554353, 7597, '2024-10-11 04:39:58', 2, 2023);
INSERT INTO `diabetes_data` VALUES (13, 'Jawa Tengah', 687988, 7895, '2024-10-11 04:39:58', 2, 2023);
INSERT INTO `diabetes_data` VALUES (14, 'DI Yogyakarta', 291721, 7220, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (15, 'Jawa Timur', 619295, 1511, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (16, 'Banten', 986453, 858, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (17, 'Bali', 865825, 8413, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (18, 'Nusa Tenggara Barat', 738282, 3893, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (19, 'Nusa Tenggara Timur', 756074, 7710, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (20, 'Kalimantan Barat', 736656, 8685, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (21, 'Kalimantan Tengah', 555814, 6599, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (22, 'Kalimantan Selatan', 172838, 6452, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (23, 'Kalimantan Timur', 373644, 2858, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (24, 'Kalimantan Utara', 147801, 1409, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (25, 'Sulawesi Utara', 698620, 1414, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (26, 'Sulawesi Tengah', 308558, 2759, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (27, 'Sulawesi Selatan', 539753, 9660, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (28, 'Sulawesi Tenggara', 284391, 7806, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (29, 'Gorontalo', 871849, 8680, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (30, 'Sulawesi Barat', 809674, 1164, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (31, 'Maluku', 450584, 7000, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (32, 'Maluku Utara', 969623, 5037, '2024-10-11 04:39:59', 2, 2023);
INSERT INTO `diabetes_data` VALUES (33, 'Papua Barat', 16544, 6285, '2024-10-11 04:40:00', 2, 2023);
INSERT INTO `diabetes_data` VALUES (34, 'Papua', 838479, 1957, '2024-10-11 04:40:00', 2, 2023);
INSERT INTO `diabetes_data` VALUES (35, 'Aceh Tenggara', 4543432, 343, '2024-10-11 05:36:36', -1, 2023);

-- ----------------------------
-- Table structure for user_settings
-- ----------------------------
DROP TABLE IF EXISTS `user_settings`;
CREATE TABLE `user_settings`  (
  `user_id` int(11) NOT NULL,
  `theme` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'light',
  `show_chart_index` tinyint(1) NULL DEFAULT 1,
  `show_chart_dashboard` tinyint(1) NULL DEFAULT 1,
  `default_sort` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'jumlah_penderita',
  PRIMARY KEY (`user_id`) USING BTREE,
  CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, 'admin', '$2a$12$eW5YG8gUoO45OOh0vF3OyONlsREKWhcqdkfehlHEbyb9eAdyMv/Du', '2024-10-11 02:02:11');

SET FOREIGN_KEY_CHECKS = 1;
