<img src="https://github.com/Tommy131/NewClickGame/blob/master/resources/S81124-230348.jpg?raw=true" alt="NewClickGame_Description1.png"/>
<img src="https://github.com/Tommy131/NewClickGame/blob/master/resources/S81124-053026.jpg?raw=true" alt="NewClickGame_Description2.png"/>


# NewClickGame
A Plugins for PocketMine-MP. NewClickGame by Teaclon/TeaTech. This is a super Game in PMMP Server!

This Plugin is compatible to version 1.1 ~ 1.7 and greater! Premise is you must install TSeriesAPI!


## Plugin Information
	* Author: Teaclon(锤子)
	* Open source license: GNU General Public License v3.0
	* Open source storage location: GitHub
	* Web from Github: https://github.com/Tommy131/NewClickGame
	* Last-Update: 2018-11-19, 18:00
	* Version: 2.9.6
    * Copyright (c) 2017-2018 TeaTech All right Reserved.


## Prerequisite
> To ensure better plugin compatibility, the plugin is compatible with the TSeriesAPI for two-way compatibility, so that you need to install the TSeriesAPI before launching the plugin.


## What is "NewClickGame"?
> This is a small game use by PocketMine-MP. This mini-game was developed primarily by clicking on a block and recording the click value and creating a click leaderboard. If you have any better ideas, please post your thoughts on the issue in this project page~


## Done Functions & TODO LIST
- [x] Command management
- [x] Smart protection game venue
- [x] Game multi-site
- [x] Game data saving
- [x] Multi-site judgment / prevention of conflict
- [x] Customize whether to prohibit WIN10 players from using
- [x] Cool particle effects that players wait to enter the game field
- [x] Eye-catching big headline display
- [x] Intelligently determine player position
- [x] Game venue configuration file management
- [x] Quickly transfer players to any game venue
- [x] Modify game venue data online and take effect immediately
- [x] FloatingText leaderboard
- [ ] Start game deduction money (custom modify from Config)
- [ ] English configuration
- [ ] English description messages in game
- [ ] Multi-languages packets


## About FloatingText leaderboard
> This FloatingText leaderboard is an independent leaderboard, which means that no matter how many game venues are set, he will focus on one place. You can set the maximum number of venues displayed per page (default is only 2 venues per page).
> ``` php
> // config index in code:
> const CONFIG_FLOATINGTEXT_LEADERBOARD = '浮空字排行榜';
> const CONFIG_LEADERBOARD_DISPLAY_PER_PAGE = "排行榜每页最大显示数量";
> ```
> ``` yaml
>  浮空字排行榜: true
>  排行榜每页最大显示数量: 2
> ```



## Plugin Copyright & Statement
	* Copyright Teaclon © 2018 All right Reserved.
	* This plugin code complies with the GPL3 license open source, please abide by this license to use this code.
	* Reprinted or used for commercial purposes, please be sure to inform the author, and indicate the original author of the code.
	* If you comply with the above statement, you will be eligible for this code.