import { expect } from '@open-wc/testing';
import sinon from 'sinon';
import DownloadManager from '../DownloadManager';

describe('DownloadManager Class', () => {
  let manager;
  let DownloaderStub;
  let downloadInstance;
  let fetchStub;

  const recordedData = [{ id: 1, name: 'test.json', status: 'pending' }];
  const file = 'test.json';

  beforeEach(() => {
    fetchStub = sinon.stub(window, 'fetch').resolves({
      ok: true,
      headers: {
        get: (key) => {
          if (key === 'Content-Length') return '123456';
          return null;
        }
      },
      json: async () => recordedData
    });

    downloadInstance = {
      ndx: 1,
      start: sinon.stub().resolves()
    };

    DownloaderStub = sinon.stub().returns(downloadInstance);

    manager = new DownloadManager(DownloaderStub);
  });

  afterEach(() => {
    fetchStub.restore();
  });

  it('should record a download with pending status', async () => {
    const data = await manager.recordDownload(file);

    expect(fetchStub).to.have.been.calledWith(`request-file/${file}`, {
      method: 'POST'
    });

    expect(data).to.equal(recordedData);

    expect(downloadInstance.start).to.have.been.not.called;

    expect(manager.activeDownloads).to.equal(0);
  });

  it('should clear history', async () => {
    const data = await manager.clearHistory();
    expect(fetchStub).to.have.been.calledWith('reset', {method: 'POST'});
    expect(data).to.equal(recordedData);
  });

  it('should return the download class and clean up active download', async () => {
    const ndx = 1;
    const status = true;
    const download = await manager.getFile(file, 1);

    expect(download, 'should return the Download class').to.equal(downloadInstance);
    expect(manager.activeDownloads, 'should have one download active').to.equal(1);

    const data = await manager.logCompleted(file, ndx, status);
    expect(fetchStub, 'should call fetch to update file status in history').to.be.calledWith(`file-status/${ndx}/${status}`, {
      method: 'POST'
    });

    expect(data.length, 'should contain 1 download in history').to.equal(1);
    expect(manager.activeDownloads, 'should have no active downloads').to.equal(0);
  });

  it('should return true if download with given index exists', async () => {
    await manager.getFile(file, 1);
    expect(manager.hasDownload(1)).to.be.true;
  });
  
  it('should return false if download with index does not exist', () => {
    expect(manager.hasDownload(999)).to.be.false;
  });

  it('should throw error if recordDownload response is not ok', async () => {
    fetchStub.resolves({ ok: false });
    try {
      await manager.recordDownload('file.json');
    } catch (err) {
      expect(err.message).to.equal('Download record failed');
    }
  });
  
  it('should throw if getFile fetch response is not ok', async () => {
    fetchStub.resolves({ ok: false, headers: { get: () => '123' } });
    try {
      await manager.getFile('bad-path', 0);
    } catch (err) {
      expect(err.message).to.contain('Failed getting file');
    }
  });
  
});
