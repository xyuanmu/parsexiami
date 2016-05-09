1. **支持链接，链接后面的** ?spm=xxx **可有可无：**
 * 单曲：[http://www.xiami.com**/song/2085857**](http://www.xiami.com/song/2085857)
 * 艺人：[http://www.xiami.com**/artist/23503**](http://www.xiami.com/artist/23503)
 * 专辑：[http://www.xiami.com**/album/168931**](http://www.xiami.com/album/168931)
 * 精选集：[http://www.xiami.com**/collect/42563832**](http://www.xiami.com/collect/42563832)
 * DEMO：[http://i.xiami.com/zhangchao**/demo/1775392054**](http://i.xiami.com/zhangchao/demo/1775392054)
 * 今日歌单（需要cookie）：[http://www.xiami.com/play?ids=**/song/playlist/id/1/type/9**](http://www.xiami.com/play?ids=/song/playlist/id/1/type/9)

2. **高级选项：**
 * 此功能一般无需使用，解析今日歌单或者国外服务器解析失败时使用。
 * 使用Cookie：首先登录虾米，登录后获取 Cookie: member_auth（[获取方法](https://raw.githubusercontent.com/xyuanmu/parsexiami/master/assets/Chrome-Cookie.gif)），打开虾米解析页面，点开高级选项，左边输入member_auth，进行解析。
 * 使用HTTP代理：如果服务器不在国内有可能会解析失败，在高级选项右边输入国内代理IP进行解析。

3. **注意事项：**
 * 本工具所有代码开源，不会收集用户信息，若不放心可以不输入 Cookie ，或者本地搭建使用。
 * 今日歌单需要用户自己的member_auth才能解析到，否则解析默认歌单。


**测试地址：**http://yuanmu.mzzhost.com/xiami/