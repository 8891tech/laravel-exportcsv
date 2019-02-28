# 8891/laravel-exportcsv Csv导出功能

ExportCsv 解决 csv 统一下载导出的功能
- [x] 客户端通过设定 Accept: text/csv 头部触发下载
- [x] 支持大文件串流下载，解决内存超出问题
- [x] 下载的 csv 可直接通过 excel 打开，无需导入
- [x] 基于 league/csv 包实现 csv 导出
- [x] 提供 ```exporting``` (正在导出) 和 ```exported``` (已导出) 事件，方便在导出前后做处理。譬如，审计
- [x] 默认注入到 web 和 api 两组路由中，并删除 ```page``` 和 ```pagesize``` 两个请求参数

## Requirement
* PHP 7 +
* Laravel 5.6 + 

## Usage

### Server
只需要 require 进来就可以，无需任何配置 
```
composer require 8891/laravel-exportcsv
```


### Client
通过 fetch 异步下载
```javascript
const downloadIfAttachment = response => {
  let contentDisposition = response.headers.get('Content-Disposition') || '';
  if (contentDisposition.toLowerCase().includes('attachment')) {
    let filename = contentDisposition.split('=').map(v => v.trim())[1];
    response.blob().then( blob => {
        let a = document.createElement("a"),
          url = URL.createObjectURL(blob);
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        setTimeout(function() {
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);  
        }, 0); 
    });
    return false;
  }
  
  return response;
}

fetch(url, {
    headers: {
        'Accept': 'text/csv'
    }
})
// content-disposition: attachment; filename=data.csv
.then(downloadIfAttachment)
.then(response => {
    // do something
});
```

## TODO
- [ ] 增加通过参数指定 export=csv 触发下载
- [ ] 增加配置自定义排除的请求参数
- [ ] 增加判断 response 是 json 或 array 才能通过 csv 下载，否则报错
